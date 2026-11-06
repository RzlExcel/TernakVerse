<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

use App\Models\User;
use App\Services\Chatbot\Contracts\ChatbotToolInterface;
use App\Services\Chatbot\Tools\GeospatialVeterinaryTools;
use Illuminate\Support\Facades\Log;

/**
 * Service orchestrating ReAct function calling tools, JSON Schema generation,
 * parameter validation, safe execution, and RBAC PII masking.
 */
class ToolExecutionService
{
    /**
     * Registered chatbot database tools keyed by tool name.
     *
     * @var array<string, ChatbotToolInterface>
     */
    protected array $tools = [];

    /**
     * Create a new ToolExecutionService instance and register tools.
     *
     * @param array<int|string, ChatbotToolInterface> $tools
     */
    public function __construct(array $tools = [])
    {
        if (empty($tools)) {
            foreach (GeospatialVeterinaryTools::all() as $tool) {
                $this->registerTool($tool);
            }
        } else {
            foreach ($tools as $tool) {
                $this->registerTool($tool);
            }
        }
    }

    /**
     * Register a new tool implementing ChatbotToolInterface.
     */
    public function registerTool(ChatbotToolInterface $tool): self
    {
        $this->tools[$tool->getName()] = $tool;

        return $this;
    }

    /**
     * Check if a tool is registered by name.
     */
    public function hasTool(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * Retrieve a registered tool by its unique name.
     */
    public function getTool(string $name): ?ChatbotToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Get all registered tools.
     *
     * @return array<string, ChatbotToolInterface>
     */
    public function getRegisteredTools(): array
    {
        return $this->tools;
    }

    /**
     * Get definitions formatted for LLM Function Calling.
     * Supports 'openai' (standard function spec) and 'gemini' (declarations format).
     *
     * @param string $format 'openai' | 'gemini'
     * @return array<int, array<string, mixed>>
     */
    public function getToolsSchema(string $format = 'openai'): array
    {
        if (strtolower($format) === 'gemini') {
            return array_map(function (ChatbotToolInterface $tool): array {
                return [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'parameters' => $tool->getParametersSchema(),
                ];
            }, array_values($this->tools));
        }

        return array_map(function (ChatbotToolInterface $tool): array {
            return [
                'type' => 'function',
                'function' => [
                    'name' => $tool->getName(),
                    'description' => $tool->getDescription(),
                    'parameters' => $tool->getParametersSchema(),
                ],
            ];
        }, array_values($this->tools));
    }

    /**
     * Safely execute a registered tool by name with arguments and RBAC PII protection.
     *
     * @param string $name
     * @param array<string, mixed> $arguments
     * @param User|null $user
     * @return array<string, mixed>
     */
    public function executeTool(string $name, array $arguments, ?User $user = null): array
    {
        $tool = $this->getTool($name);
        if (! $tool) {
            return [
                'status' => 'error',
                'message' => "Tool '{$name}' tidak ditemukan atau belum terdaftar dalam registry.",
            ];
        }

        // Validate required arguments based on tool JSON Schema
        $schema = $tool->getParametersSchema();
        $requiredFields = $schema['required'] ?? [];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (! array_key_exists($field, $arguments) || $arguments[$field] === null || trim((string) $arguments[$field]) === '') {
                $missingFields[] = $field;
            }
        }

        if (! empty($missingFields)) {
            return [
                'status' => 'error',
                'message' => "Parameter wajib tidak lengkap untuk '{$name}': " . implode(', ', $missingFields),
            ];
        }

        try {
            $rawResult = $tool->execute($arguments);

            return $this->applyRbacAndMaskPii($rawResult, $user);
        } catch (\Throwable $e) {
            Log::error("Chatbot tool execution failed for '{$name}': " . $e->getMessage(), [
                'tool' => $name,
                'arguments' => $arguments,
                'user_id' => $user?->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'message' => "Terjadi kesalahan internal saat mengeksekusi tool '{$name}': " . $e->getMessage(),
            ];
        }
    }

    /**
     * Apply Role-Based Access Control (RBAC) and mask sensitive Personally Identifiable Information (PII).
     *
     * @param array<string, mixed> $data
     * @param User|null $user
     * @return array<string, mixed>
     */
    protected function applyRbacAndMaskPii(array $data, ?User $user): array
    {
        if ($this->isAuthorizedForPii($user)) {
            return $data;
        }

        /** @var array<string, mixed> $masked */
        $masked = $this->maskPii($data);

        return $masked;
    }

    /**
     * Determine if the user has authorization to view unmasked PII.
     */
    protected function isAuthorizedForPii(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        // Super Admin, Dinas Admin, or general Admin
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            return true;
        }

        // Check dynamic permission if available
        try {
            if ($user->hasPermission('cases.view_pii') || $user->hasPermission('cases.view')) {
                return true;
            }
        } catch (\Throwable) {
            // Permission service unavailable or tables not yet seeded
        }

        return false;
    }

    /**
     * Recursively mask sensitive fields and embedded phone numbers.
     */
    protected function maskPii(mixed $data): mixed
    {
        if (! is_array($data)) {
            if (is_string($data)) {
                // Mask telephone numbers embedded in text: e.g. 0812-3456-7890 or +628...
                return (string) preg_replace('/(\+?62|08)\d{2}[-.\s]?\d{3,4}[-.\s]?\d{3,5}/', '[NOMOR KONTAK DISEMBUNYIKAN]', $data);
            }

            return $data;
        }

        $masked = [];
        $sensitiveKeys = [
            'nomor_kontak',
            'kontak',
            'telepon',
            'no_telepon',
            'no_telp',
            'no_hp',
            'phone',
            'penanggung_jawab',
            'nama_pemilik',
            'nama_peternak',
            'nik',
            'nik_peternak',
        ];

        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $sensitiveKeys, true)) {
                if ($value !== null && $value !== '') {
                    $masked[$key] = '[DISEMBUNYIKAN - HAK AKSES KHUSUS PETUGAS/DINAS]';
                } else {
                    $masked[$key] = null;
                }
            } elseif (is_array($value)) {
                $masked[$key] = $this->maskPii($value);
            } elseif (is_string($value)) {
                $masked[$key] = $this->maskPii($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }
}
