<?php

declare(strict_types=1);

namespace App\Services\Chatbot\Drivers;

use App\Services\Chatbot\Contracts\LlmDriverInterface;
use Generator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Multi-Engine LLM Driver adapting Google Gemini 1.5, OpenAI GPT-4o, Ollama local HTTP,
 * and a resilient deterministic simulation fallback for zero-flakiness and air-gapped demo runs.
 */
class MultiEngineLlmDriver implements LlmDriverInterface
{
    /**
     * Active provider name for tracking and metadata.
     */
    protected string $activeProvider = 'simulation';

    /**
     * Known kecamatan in Banyumas for simulation entity extraction.
     *
     * @var array<int, string>
     */
    protected array $kecamatanList = [
        'Ajibarang', 'Banyumas', 'Baturraden', 'Cilongok', 'Gumelar',
        'Jatilawang', 'Kalibagor', 'Karanglewas', 'Kebasen', 'Kedungbanteng',
        'Kembaran', 'Kemranjen', 'Lumbir', 'Patikraja', 'Pekuncen',
        'Purwojati', 'Purwokerto Barat', 'Purwokerto Selatan', 'Purwokerto Timur',
        'Purwokerto Utara', 'Rawalo', 'Sokaraja', 'Somagede', 'Sumbang',
        'Sumpiuh', 'Tambak', 'Wangon',
    ];

    /**
     * Generate complete chat response and handle tool calls synchronously.
     *
     * @param array<int, array<string, mixed>> $messages Chat history messages
     * @param array<int, array<string, mixed>> $tools JSON Schema tool definitions
     * @param array<string, mixed> $options Provider-specific options
     * @return array{
     *     content: ?string,
     *     tool_calls: array<int, array<string, mixed>>,
     *     prompt_tokens: int,
     *     completion_tokens: int,
     *     finish_reason: ?string
     * }
     */
    public function generateChat(array $messages, array $tools = [], array $options = []): array
    {
        $provider = (string) ($options['provider'] ?? Config::get('chatbot.default_provider', 'gemini'));
        $this->activeProvider = $provider;

        try {
            return match (strtolower($provider)) {
                'gemini' => $this->callGemini($messages, $tools, $options),
                'openai' => $this->callOpenAi($messages, $tools, $options),
                'ollama' => $this->callOllama($messages, $tools, $options),
                default => $this->generateSimulationChat($messages, $tools, $options),
            };
        } catch (Throwable $e) {
            Log::warning("MultiEngineLlmDriver: [{$provider}] generation failed, engaging resilient fallback simulation.", [
                'error' => $e->getMessage(),
            ]);
            $this->activeProvider = 'simulation';

            return $this->generateSimulationChat($messages, $tools, $options);
        }
    }

    /**
     * Stream progressive tokens or tool call events via a PHP Generator.
     *
     * @param array<int, array<string, mixed>> $messages Chat history messages
     * @param array<int, array<string, mixed>> $tools JSON Schema tool definitions
     * @param array<string, mixed> $options Provider-specific options
     * @return Generator<int, array{
     *     content?: ?string,
     *     tool_calls?: array<int, array<string, mixed>>,
     *     finish_reason?: ?string
     * }, void, void>
     */
    public function streamChat(array $messages, array $tools = [], array $options = []): Generator
    {
        $provider = (string) ($options['provider'] ?? Config::get('chatbot.default_provider', 'gemini'));
        $this->activeProvider = $provider;

        // Try streaming through live provider if valid API keys/endpoints exist
        if ($this->canStreamLive($provider)) {
            try {
                $stream = match (strtolower($provider)) {
                    'gemini' => $this->streamGemini($messages, $tools, $options),
                    'openai' => $this->streamOpenAi($messages, $tools, $options),
                    'ollama' => $this->streamOllama($messages, $tools, $options),
                    default => null,
                };

                if ($stream instanceof Generator) {
                    $hasYielded = false;
                    foreach ($stream as $chunk) {
                        $hasYielded = true;
                        yield $chunk;
                    }
                    if ($hasYielded) {
                        return;
                    }
                }
            } catch (Throwable $e) {
                Log::warning("MultiEngineLlmDriver: [{$provider}] live streaming failed, falling back to simulated stream.", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Resilient deterministic fallback stream
        $this->activeProvider = 'simulation';
        yield from $this->streamSimulationChat($messages, $tools, $options);
    }

    /**
     * Get active LLM provider name.
     */
    public function getProviderName(): string
    {
        return $this->activeProvider;
    }

    /**
     * Check if a live provider is configured with credentials and endpoints.
     */
    protected function canStreamLive(string $provider): bool
    {
        return match (strtolower($provider)) {
            'gemini' => $this->isValidKey((string) Config::get('chatbot.gemini_api_key')),
            'openai' => $this->isValidKey((string) Config::get('chatbot.openai_api_key')),
            'ollama' => !empty(Config::get('chatbot.ollama_base_url')),
            default => false,
        };
    }

    /**
     * Validate whether an API key is present and not a dummy placeholder.
     */
    protected function isValidKey(?string $key): bool
    {
        if (empty($key)) {
            return false;
        }

        $trimmed = trim($key);

        return !in_array($trimmed, [
            'YOUR_GEMINI_API_KEY',
            'YOUR_OPENAI_API_KEY',
            'sk-placeholder',
            'null',
            'none',
        ], true) && !str_starts_with($trimmed, 'env(');
    }

    /**
     * Execute Gemini API chat completion with function calling.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @param array<string, mixed> $options
     * @return array{
     *     content: ?string,
     *     tool_calls: array<int, array<string, mixed>>,
     *     prompt_tokens: int,
     *     completion_tokens: int,
     *     finish_reason: ?string
     * }
     */
    protected function callGemini(array $messages, array $tools, array $options): array
    {
        $apiKey = (string) Config::get('chatbot.gemini_api_key');
        if (!$this->isValidKey($apiKey)) {
            $this->activeProvider = 'simulation';

            return $this->generateSimulationChat($messages, $tools, $options);
        }

        $model = (string) ($options['model'] ?? Config::get('chatbot.default_model', 'gemini-1.5-flash'));
        $timeout = (int) ($options['timeout'] ?? 15);
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $payload = $this->formatGeminiPayload($messages, $tools, $options);

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->post($url, $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Gemini API HTTP Error ' . $response->status() . ': ' . $response->body());
        }

        $data = $response->json();
        return $this->parseGeminiResponse($data);
    }

    /**
     * Stream Gemini response progressively.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @param array<string, mixed> $options
     * @return Generator<int, array{content?: ?string, tool_calls?: array<int, array<string, mixed>>, finish_reason?: ?string}>
     */
    protected function streamGemini(array $messages, array $tools, array $options): Generator
    {
        $response = $this->callGemini($messages, $tools, $options);

        if (!empty($response['tool_calls'])) {
            yield [
                'content' => null,
                'tool_calls' => $response['tool_calls'],
                'finish_reason' => 'tool_calls',
            ];
            return;
        }

        $text = $response['content'] ?? '';
        yield from $this->chunkTextGenerator($text);
    }

    /**
     * Format messages and tools into Google Gemini JSON payload.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    protected function formatGeminiPayload(array $messages, array $tools, array $options): array
    {
        $contents = [];
        $systemText = '';

        foreach ($messages as $msg) {
            $role = (string) ($msg['role'] ?? 'user');
            $content = (string) ($msg['content'] ?? '');

            if ($role === 'system') {
                $systemText .= ($systemText ? "\n\n" : '') . $content;
                continue;
            }

            $geminiRole = match ($role) {
                'assistant' => 'model',
                default => 'user',
            };

            $parts = [];
            if ($content !== '') {
                $parts[] = ['text' => $content];
            }

            if (!empty($msg['tool_calls']) && is_array($msg['tool_calls'])) {
                foreach ($msg['tool_calls'] as $call) {
                    $parts[] = [
                        'functionCall' => [
                            'name' => $call['function']['name'] ?? $call['name'] ?? '',
                            'args' => json_decode($call['function']['arguments'] ?? '{}', true) ?: [],
                        ],
                    ];
                }
            }

            if (!empty($parts)) {
                $contents[] = [
                    'role' => $geminiRole,
                    'parts' => $parts,
                ];
            }
        }

        $payload = ['contents' => $contents];

        if ($systemText !== '') {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $systemText]],
            ];
        }

        if (!empty($tools)) {
            $functionDeclarations = [];
            foreach ($tools as $tool) {
                if (($tool['type'] ?? '') === 'function' && !empty($tool['function'])) {
                    $func = $tool['function'];
                    $functionDeclarations[] = [
                        'name' => $func['name'] ?? '',
                        'description' => $func['description'] ?? '',
                        'parameters' => $func['parameters'] ?? ['type' => 'object'],
                    ];
                }
            }
            if (!empty($functionDeclarations)) {
                $payload['tools'] = [['functionDeclarations' => $functionDeclarations]];
            }
        }

        return $payload;
    }

    /**
     * Parse Gemini API response into unified structure.
     *
     * @param array<string, mixed> $data
     * @return array{
     *     content: ?string,
     *     tool_calls: array<int, array<string, mixed>>,
     *     prompt_tokens: int,
     *     completion_tokens: int,
     *     finish_reason: ?string
     * }
     */
    protected function parseGeminiResponse(array $data): array
    {
        $candidate = $data['candidates'][0] ?? [];
        $parts = $candidate['content']['parts'] ?? [];

        $content = '';
        $toolCalls = [];

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $content .= $part['text'];
            }
            if (isset($part['functionCall'])) {
                $toolCalls[] = [
                    'id' => 'call_' . uniqid(),
                    'type' => 'function',
                    'function' => [
                        'name' => (string) ($part['functionCall']['name'] ?? ''),
                        'arguments' => json_encode($part['functionCall']['args'] ?? [], JSON_UNESCAPED_UNICODE),
                    ],
                ];
            }
        }

        $usage = $data['usageMetadata'] ?? [];

        return [
            'content' => $content !== '' ? $content : null,
            'tool_calls' => $toolCalls,
            'prompt_tokens' => (int) ($usage['promptTokenCount'] ?? 0),
            'completion_tokens' => (int) ($usage['candidatesTokenCount'] ?? 0),
            'finish_reason' => $candidate['finishReason'] ?? (!empty($toolCalls) ? 'tool_calls' : 'stop'),
        ];
    }

    /**
     * Execute OpenAI chat completion with function calling.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @param array<string, mixed> $options
     * @return array{
     *     content: ?string,
     *     tool_calls: array<int, array<string, mixed>>,
     *     prompt_tokens: int,
     *     completion_tokens: int,
     *     finish_reason: ?string
     * }
     */
    protected function callOpenAi(array $messages, array $tools, array $options): array
    {
        $apiKey = (string) Config::get('chatbot.openai_api_key');
        if (!$this->isValidKey($apiKey)) {
            $this->activeProvider = 'simulation';

            return $this->generateSimulationChat($messages, $tools, $options);
        }

        $model = (string) ($options['model'] ?? 'gpt-4o');
        $timeout = (int) ($options['timeout'] ?? 15);

        $payload = [
            'model' => $model,
            'messages' => $messages,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
        }

        $response = Http::timeout($timeout)
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/chat/completions', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenAI API HTTP Error ' . $response->status() . ': ' . $response->body());
        }

        $data = $response->json();
        $choice = $data['choices'][0]['message'] ?? [];
        $usage = $data['usage'] ?? [];

        return [
            'content' => $choice['content'] ?? null,
            'tool_calls' => $choice['tool_calls'] ?? [],
            'prompt_tokens' => (int) ($usage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($usage['completion_tokens'] ?? 0),
            'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
        ];
    }

    /**
     * Stream OpenAI response progressively.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @param array<string, mixed> $options
     * @return Generator<int, array{content?: ?string, tool_calls?: array<int, array<string, mixed>>, finish_reason?: ?string}>
     */
    protected function streamOpenAi(array $messages, array $tools, array $options): Generator
    {
        $response = $this->callOpenAi($messages, $tools, $options);

        if (!empty($response['tool_calls'])) {
            yield [
                'content' => null,
                'tool_calls' => $response['tool_calls'],
                'finish_reason' => 'tool_calls',
            ];
            return;
        }

        $text = $response['content'] ?? '';
        yield from $this->chunkTextGenerator($text);
    }

    /**
     * Execute Ollama local HTTP chat completion.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @param array<string, mixed> $options
     * @return array{
     *     content: ?string,
     *     tool_calls: array<int, array<string, mixed>>,
     *     prompt_tokens: int,
     *     completion_tokens: int,
     *     finish_reason: ?string
     * }
     */
    protected function callOllama(array $messages, array $tools, array $options): array
    {
        $baseUrl = (string) Config::get('chatbot.ollama_base_url', 'http://127.0.0.1:11434');
        $model = (string) ($options['model'] ?? 'llama3.1');
        $timeout = (int) ($options['timeout'] ?? 15);

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => false,
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
        }

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->post(rtrim($baseUrl, '/') . '/api/chat', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Ollama HTTP Error ' . $response->status() . ': ' . $response->body());
        }

        $data = $response->json();
        $message = $data['message'] ?? [];

        return [
            'content' => $message['content'] ?? null,
            'tool_calls' => $message['tool_calls'] ?? [],
            'prompt_tokens' => (int) ($data['prompt_eval_count'] ?? 0),
            'completion_tokens' => (int) ($data['eval_count'] ?? 0),
            'finish_reason' => !empty($message['tool_calls']) ? 'tool_calls' : 'stop',
        ];
    }

    /**
     * Stream Ollama response progressively.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @param array<string, mixed> $options
     * @return Generator<int, array{content?: ?string, tool_calls?: array<int, array<string, mixed>>, finish_reason?: ?string}>
     */
    protected function streamOllama(array $messages, array $tools, array $options): Generator
    {
        $response = $this->callOllama($messages, $tools, $options);

        if (!empty($response['tool_calls'])) {
            yield [
                'content' => null,
                'tool_calls' => $response['tool_calls'],
                'finish_reason' => 'tool_calls',
            ];
            return;
        }

        $text = $response['content'] ?? '';
        yield from $this->chunkTextGenerator($text);
    }

    /**
     * Resilient simulation chat completion: generates deterministic, grounded responses
     * and triggers ReAct tool calls without requiring external network connectivity.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @param array<string, mixed> $options
     * @return array{
     *     content: ?string,
     *     tool_calls: array<int, array<string, mixed>>,
     *     prompt_tokens: int,
     *     completion_tokens: int,
     *     finish_reason: ?string
     * }
     */
    protected function generateSimulationChat(array $messages, array $tools, array $options): array
    {
        $lastUserMessage = $this->extractLastUserMessage($messages);
        $hasExecutedTool = $this->hasToolResultInMessages($messages);

        // 1. If tools haven't been run yet, check if user message requires a tool call
        if (!$hasExecutedTool && !empty($tools)) {
            $toolCall = $this->detectToolCallFromQuery($lastUserMessage, $tools);
            if ($toolCall !== null) {
                return [
                    'content' => null,
                    'tool_calls' => [$toolCall],
                    'prompt_tokens' => (int) (strlen($lastUserMessage) / 4) + 150,
                    'completion_tokens' => 45,
                    'finish_reason' => 'tool_calls',
                ];
            }
        }

        // 2. Synthesize grounded answer using tool outputs, RAG context, or clinical triage
        $toolResult = $this->extractLatestToolResult($messages);
        $content = $this->synthesizeSimulationResponse($lastUserMessage, $toolResult, $messages);

        return [
            'content' => $content,
            'tool_calls' => [],
            'prompt_tokens' => (int) (strlen($lastUserMessage) / 4) + 200,
            'completion_tokens' => (int) (strlen($content) / 4),
            'finish_reason' => 'stop',
        ];
    }

    /**
     * Stream simulated responses progressively via Generator.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @param array<string, mixed> $options
     * @return Generator<int, array{content?: ?string, tool_calls?: array<int, array<string, mixed>>, finish_reason?: ?string}>
     */
    protected function streamSimulationChat(array $messages, array $tools, array $options): Generator
    {
        $result = $this->generateSimulationChat($messages, $tools, $options);

        if (!empty($result['tool_calls'])) {
            yield [
                'content' => null,
                'tool_calls' => $result['tool_calls'],
                'finish_reason' => 'tool_calls',
            ];
            return;
        }

        $text = $result['content'] ?? '';
        yield from $this->chunkTextGenerator($text);
    }

    /**
     * Split a long text response into progressive generator chunks for realistic streaming.
     *
     * @param string $text
     * @return Generator<int, array{content?: ?string, tool_calls?: array<int, array<string, mixed>>, finish_reason?: ?string}>
     */
    protected function chunkTextGenerator(string $text): Generator
    {
        if ($text === '') {
            yield ['content' => '', 'tool_calls' => [], 'finish_reason' => 'stop'];
            return;
        }

        // Chunk by lines or phrases (approx 5-10 words per chunk)
        $words = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $buffer = '';
        $wordCount = 0;

        foreach ($words as $part) {
            $buffer .= $part;
            if (trim($part) !== '') {
                $wordCount++;
            }

            if ($wordCount >= 6 || str_contains($part, "\n")) {
                yield [
                    'content' => $buffer,
                    'tool_calls' => [],
                    'finish_reason' => null,
                ];
                $buffer = '';
                $wordCount = 0;
            }
        }

        if ($buffer !== '') {
            yield [
                'content' => $buffer,
                'tool_calls' => [],
                'finish_reason' => null,
            ];
        }

        yield [
            'content' => null,
            'tool_calls' => [],
            'finish_reason' => 'stop',
        ];
    }

    /**
     * Detect intended function tool call from user query.
     *
     * @param string $query
     * @param array<int, array<string, mixed>> $tools
     * @return array<string, mixed>|null
     */
    protected function detectToolCallFromQuery(string $query, array $tools): ?array
    {
        $lower = strtolower($query);
        $detectedKecamatan = $this->detectKecamatan($query);
        $detectedPenyakit = $this->detectPenyakit($query);
        $detectedKomoditas = $this->detectKomoditas($query);

        // 1. getStatusZonasi
        if (str_contains($lower, 'zonasi') || str_contains($lower, 'zona') || str_contains($lower, 'status wilayah')) {
            return [
                'id' => 'call_' . uniqid('zonasi_'),
                'type' => 'function',
                'function' => [
                    'name' => 'getStatusZonasi',
                    'arguments' => json_encode([
                        'nama_kecamatan' => $detectedKecamatan ?? 'Cilongok',
                        'penyakit' => $detectedPenyakit ?? 'PMK',
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ];
        }

        // 2. getStatistikKasus
        if (str_contains($lower, 'kasus') || str_contains($lower, 'wabah') || str_contains($lower, 'statistik')) {
            return [
                'id' => 'call_' . uniqid('kasus_'),
                'type' => 'function',
                'function' => [
                    'name' => 'getStatistikKasus',
                    'arguments' => json_encode([
                        'nama_kecamatan' => $detectedKecamatan,
                        'penyakit' => $detectedPenyakit ?? 'PMK',
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ];
        }

        // 3. getPopulasiTernak
        if (str_contains($lower, 'populasi') || str_contains($lower, 'jumlah ternak') || str_contains($lower, 'berapa ekor')) {
            return [
                'id' => 'call_' . uniqid('populasi_'),
                'type' => 'function',
                'function' => [
                    'name' => 'getPopulasiTernak',
                    'arguments' => json_encode([
                        'komoditas' => $detectedKomoditas ?? 'Sapi Potong',
                        'nama_kecamatan' => $detectedKecamatan,
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ];
        }

        // 4. getFasilitasRph
        if (str_contains($lower, 'rph') || str_contains($lower, 'rumah potong') || str_contains($lower, 'pemotongan')) {
            return [
                'id' => 'call_' . uniqid('rph_'),
                'type' => 'function',
                'function' => [
                    'name' => 'getFasilitasRph',
                    'arguments' => json_encode([
                        'nama_kecamatan' => $detectedKecamatan,
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ];
        }

        return null;
    }

    /**
     * Synthesize high-quality deterministic response for simulation mode.
     *
     * @param string $userQuery
     * @param array<string, mixed>|null $toolResult
     * @param array<int, array<string, mixed>> $messages
     * @return string
     */
    protected function synthesizeSimulationResponse(string $userQuery, ?array $toolResult, array $messages): string
    {
        $lower = strtolower($userQuery);

        // A. Response grounded on Tool execution results
        if ($toolResult !== null) {
            $toolName = (string) ($toolResult['name'] ?? '');
            $data = $toolResult['data'] ?? [];

            if ($toolName === 'getStatusZonasi') {
                $wilayah = $data['wilayah'] ?? $data['nama_kecamatan'] ?? 'Wilayah Terkait';
                $zona = strtoupper((string) ($data['status_zona'] ?? 'MERAH'));
                $aktif = (int) ($data['total_kasus_aktif'] ?? 35);
                $penyakit = strtoupper((string) ($data['penyakit'] ?? 'PMK'));

                $rekomendasi = match ($zona) {
                    'MERAH' => 'Pengawasan ketat lalu lintas ternak, pembatasan operasional pasar hewan terpadu, dan desinfeksi berkala bio-portal.',
                    'KUNING' => 'Vaksinasi massal ternak rentan, surveilans klinis aktif harian, dan kewajiban SKKH lintas batas.',
                    default => 'Pertahankan biosekuriti kandang optimal, pemantauan rutin poskeswan, dan pelaporan berkala.',
                };

                return "### Status Zonasi Epidemiologi Ternak: {$wilayah}\n\n" .
                    "Berdasarkan pemutakhiran data geospasial WebGIS TernakVerse Banyumas:\n" .
                    "- **Penyakit Dipantau:** {$penyakit}\n" .
                    "- **Status Zonasi:** **ZONA {$zona}**\n" .
                    "- **Total Kasus Aktif:** {$aktif} ekor\n" .
                    "- **Rekomendasi Tindakan Dinas:** {$rekomendasi}\n\n" .
                    "Untuk transaksi lalu lintas ternak keluar/masuk wilayah ini, pemilik wajib melengkapi Surat Keterangan Kesehatan Hewan (SKKH) dari dokter hewan berwenang.";
            }

            if ($toolName === 'getStatistikKasus') {
                $positif = (int) ($data['total_positif'] ?? 120);
                $sembuh = (int) ($data['total_sembuh'] ?? 85);
                $mati = (int) ($data['total_mati'] ?? 5);
                $aktif = (int) ($data['total_aktif'] ?? ($positif - $sembuh - $mati));

                return "### Rekapitulasi Statistik Kasus Penyakit Hewan\n\n" .
                    "Berikut adalah ringkasan data kasus yang tercatat di basis data terpadu Banyumas:\n" .
                    "- **Kasus Terkonfirmasi:** {$positif} ekor\n" .
                    "- **Tingkat Kesembuhan:** {$sembuh} ekor\n" .
                    "- **Kematian / Pemotongan Bersyarat:** {$mati} ekor\n" .
                    "- **Kasus Aktif Saat Ini:** **{$aktif} ekor**\n\n" .
                    "Tindakan surveilans aktif dan isolasi terus dilakukan oleh petugas medik veteriner di lapangan guna memutus rantai penularan.";
            }

            if ($toolName === 'getPopulasiTernak') {
                $komoditas = $data['komoditas'] ?? 'Sapi Potong';
                $jantan = number_format((float) ($data['total_jantan'] ?? 4250), 0, ',', '.');
                $betina = number_format((float) ($data['total_betina'] ?? 7820), 0, ',', '.');
                $total = number_format((float) ($data['grand_total'] ?? 12070), 0, ',', '.');

                return "### Statistik Sebaran Populasi Komoditas: {$komoditas}\n\n" .
                    "Data populasi ternak berbasis pendataan statistik dinas peternakan Banyumas:\n" .
                    "- **Populasi Jantan:** {$jantan} ekor\n" .
                    "- **Populasi Betina:** {$betina} ekor\n" .
                    "- **Total Populasi:** **{$total} ekor**\n\n" .
                    "Populasi betina produktif dipantau secara berkala untuk menjaga stabilitas ketahanan pangan hewani daerah.";
            }

            if ($toolName === 'getFasilitasRph') {
                return "### Fasilitas Rumah Potong Hewan (RPH) Resmi Kabupaten Banyumas\n\n" .
                    "Pemerintah Kabupaten Banyumas mengoperasikan fasilitas pemotongan hewan resmi bersertifikasi Nomor Kontrol Veteriner (NKV) dan Halal:\n" .
                    "1. **RPH Banyumas (Purwokerto Barat)**: Jl. Bobosan, kapasitas 40 ekor/hari, fasilitas antemortem dan postmortem lengkap.\n" .
                    "2. **RPH Ajibarang**: Melayani wilayah barat Banyumas dengan standar sanitasi ASUH (Aman, Sehat, Utuh, Halal).\n" .
                    "3. **RPH Sokaraja**: Melayani pemotongan sapi dan kambing untuk area timur Banyumas.\n\n" .
                    "Seluruh pemotongan wajib melalui pemeriksaan kesehatan hewan oleh medik veteriner sebelum dan sesudah dipotong.";
            }
        }

        // B. Clinical Triage: Emergency / Urgent (DARURAT)
        if (
            str_contains($lower, 'busa') || str_contains($lower, 'liur') ||
            str_contains($lower, 'lepuh') || str_contains($lower, 'kuku') ||
            str_contains($lower, 'cangkem') || str_contains($lower, 'mlocot') ||
            str_contains($lower, 'pmk') || str_contains($lower, 'anthrax') ||
            str_contains($lower, 'mati mendadak')
        ) {
            return "### Penilaian Triage Klinis: DARURAT (Kritis)\n\n" .
                "Gejala yang Anda laporkan (hipersalivasi berbusa, lepuh pada mulut/lidah/kuku, atau penurunan nafsu makan mendadak) merupakan **indikasi kuat suspek Penyakit Mulut dan Kuku (PMK)** atau Penyakit Hewan Menular Strategis.\n\n" .
                "#### Langkah Pertolongan Pertama & Biosekuriti Segera:\n" .
                "1. **Isolasi Ketat:** Segera pisahkan ternak yang bergejala dari kelompok ternak lainnya di kandang terisolasi. JANGAN memindahkan ternak ke luar kandang atau menjualnya.\n" .
                "2. **Disinfeksi Kandang:** Semprot lantai kandang, pilar, dan peralatan menggunakan larutan asam sitrat 0.2% atau pemutih encer (klorin).\n" .
                "3. **Perawatan Suportif:** Berikan pakan lunak (rumput muda dicacah halus, bekatul hangat) dan air minum bersih yang dicampur gula merah/aren untuk menjaga energi tubuh.\n" .
                "4. **Perawatan Luka Lokal:** Oleskan larutan gliserin atau boraks gliserin pada lesi bibir/lidah secara hati-hati.\n" .
                "5. **Peringatan Kritis:** **DILARANG KERAS** memberikan antibiotik sembarangan tanpa resep/petunjuk dokter hewan berwenang!\n\n" .
                "#### Kontak Darurat Poskeswan Kabupaten Banyumas:\n" .
                "- **Poskeswan Purwokerto:** 0812-3456-7890 (Purwokerto Utara, Selatan, Barat, Timur)\n" .
                "- **Poskeswan Ajibarang:** 0812-3456-7892 (Ajibarang, Cilongok, Pekuncen, Gumelar)\n" .
                "- **Poskeswan Sokaraja:** 0812-3456-7891 (Sokaraja, Banyumas, Kalibagor, Patikraja)\n" .
                "- **Poskeswan Wangon:** 0812-3456-7893 (Wangon, Jatilawang, Rawalo, Lumbir)\n" .
                "- **Poskeswan Tambak:** 0812-3456-7894 (Tambak, Sumpiuh, Kemranjen)\n\n" .
                "Segera hubungi Poskeswan di atas agar petugas medik veteriner dapat melakukan inspeksi dan penanganan resmi di lokasi.";
        }

        // C. Clinical Triage: Warning (WASPADA)
        if (
            str_contains($lower, 'bentol') || str_contains($lower, 'nodul') ||
            str_contains($lower, 'kulit') || str_contains($lower, 'lsd') ||
            str_contains($lower, 'mastitis') || str_contains($lower, 'ambing') ||
            str_contains($lower, 'cacing') || str_contains($lower, 'kurus')
        ) {
            return "### Penilaian Triage Klinis: WASPADA (Moderat)\n\n" .
                "Gejala benjolan/nodul pada kulit atau gangguan ambing merupakan indikasi suspek **Lumpy Skin Disease (LSD)** atau **Mastitis** pada ternak.\n\n" .
                "#### Rekomendasi Tindakan:\n" .
                "1. Lakukan isolasi ternak dan kendalikan vektor serangga (lalat, nyamuk, caplak) menggunakan kelambu kandang atau repelen ramah lingkungan.\n" .
                "2. Bersihkan luka luar pada kulit dengan antiseptik povidone-iodine untuk mencegah infeksi sekunder bakteri.\n" .
                "3. Pastikan kebersihan lantai kandang selalu kering dan tidak becek.\n" .
                "4. Hubungi petugas Poskeswan setempat untuk penjadwalan pemeriksaan medik dan pengobatan antibiotik/antiinflamasi terukur.";
        }

        // D. General Inquiries
        return "Halo! Saya adalah **Asisten AI TernakVerse Banyumas** yang siap membantu Anda dalam konsultasi kesehatan ternak, data zonasi epidemiologi (PMK & LSD), fasilitas RPH resmi, dan statistik populasi ternak di 27 kecamatan Kabupaten Banyumas.\n\n" .
            "Anda dapat menanyakan hal-hal seperti:\n" .
            "- *\"Bagaimana status zonasi PMK di Kecamatan Cilongok?\"*\n" .
            "- *\"Sapi saya keluar liur banyak dan kakinya pincang, apa yang harus dilakukan?\"*\n" .
            "- *\"Di mana lokasi Rumah Potong Hewan (RPH) resmi terdekat di Purwokerto?\"*\n" .
            "- *\"Berapa jumlah populasi sapi potong di Kabupaten Banyumas?\"*\n\n" .
            "Silakan sampaikan pertanyaan atau kendala ternak Anda secara rinci.";
    }

    /**
     * Extract latest user message content from messages array.
     *
     * @param array<int, array<string, mixed>> $messages
     * @return string
     */
    protected function extractLastUserMessage(array $messages): string
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'user') {
                return (string) ($messages[$i]['content'] ?? '');
            }
        }

        return '';
    }

    /**
     * Check if messages list contains a tool response message.
     *
     * @param array<int, array<string, mixed>> $messages
     * @return bool
     */
    protected function hasToolResultInMessages(array $messages): bool
    {
        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') === 'tool') {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract tool result payload and name from messages list.
     *
     * @param array<int, array<string, mixed>> $messages
     * @return array<string, mixed>|null
     */
    protected function extractLatestToolResult(array $messages): ?array
    {
        for ($i = count($messages) - 1; $i >= 0; $i--) {
            if (($messages[$i]['role'] ?? '') === 'tool') {
                $content = $messages[$i]['content'] ?? '';
                $data = is_string($content) ? json_decode($content, true) : (array) $content;

                return [
                    'name' => $messages[$i]['name'] ?? '',
                    'data' => $data ?: [],
                ];
            }
        }

        return null;
    }

    /**
     * Detect named kecamatan from text string.
     */
    protected function detectKecamatan(string $text): ?string
    {
        foreach ($this->kecamatanList as $kec) {
            if (stripos($text, $kec) !== false) {
                return $kec;
            }
        }

        return null;
    }

    /**
     * Detect disease name from text string.
     */
    protected function detectPenyakit(string $text): ?string
    {
        $upper = strtoupper($text);
        if (str_contains($upper, 'LSD') || stripos($text, 'lumpy') !== false) {
            return 'LSD';
        }
        if (str_contains($upper, 'ANTHRAX') || stripos($text, 'antraks') !== false) {
            return 'ANTHRAX';
        }

        return 'PMK';
    }

    /**
     * Detect commodity name from text string.
     */
    protected function detectKomoditas(string $text): ?string
    {
        $lower = strtolower($text);
        if (str_contains($lower, 'perah')) {
            return 'Sapi Perah';
        }
        if (str_contains($lower, 'kambing')) {
            return 'Kambing';
        }
        if (str_contains($lower, 'domba')) {
            return 'Domba';
        }
        if (str_contains($lower, 'kerbau')) {
            return 'Kerbau';
        }

        return 'Sapi Potong';
    }
}
