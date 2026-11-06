<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service to generate dense vector embeddings across multiple AI providers
 * with instant deterministic offline fallback for tests and development.
 */
class EmbeddingService
{
    /**
     * Generate vector embedding for the given input text.
     *
     * @param string $text
     * @return array<int, float>
     */
    public function generateEmbedding(string $text): array
    {
        $provider = (string) config('chatbot.embedding_provider', 'gemini');
        $dimension = $this->getDimension();

        if ($provider === 'gemini') {
            $apiKey = config('chatbot.gemini_api_key');
            if (!empty($apiKey)) {
                $embedding = $this->embedWithGemini($text, (string) $apiKey, $dimension);
                if ($embedding !== null) {
                    return $embedding;
                }
            }
        } elseif ($provider === 'openai') {
            $apiKey = config('chatbot.openai_api_key');
            if (!empty($apiKey)) {
                $embedding = $this->embedWithOpenAi($text, (string) $apiKey, $dimension);
                if ($embedding !== null) {
                    return $embedding;
                }
            }
        }

        // Default or offline fallback
        return $this->generateDeterministicEmbedding($text, $dimension);
    }

    /**
     * Get the configured embedding dimension.
     */
    public function getDimension(): int
    {
        return (int) config('chatbot.embedding_dimension', 1536);
    }

    /**
     * Compute cosine similarity between two float vectors.
     * Range: -1.0 to 1.0 (clamped).
     *
     * @param array<int, float|int> $vecA
     * @param array<int, float|int> $vecB
     * @return float
     */
    public function cosineSimilarity(array $vecA, array $vecB): float
    {
        $count = min(count($vecA), count($vecB));
        if ($count === 0) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < $count; $i++) {
            $a = (float) $vecA[$i];
            $b = (float) $vecB[$i];
            $dotProduct += $a * $b;
            $normA += $a * $a;
            $normB += $b * $b;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        $similarity = $dotProduct / (sqrt($normA) * sqrt($normB));

        return (float) max(-1.0, min(1.0, $similarity));
    }

    /**
     * Compute a deterministic normalized float vector derived from SHA-256 hash segments.
     * Guarantees 100% test reliability and offline zero-cost execution.
     *
     * @param string $text
     * @param int|null $dimension
     * @return array<int, float>
     */
    public function generateDeterministicEmbedding(string $text, ?int $dimension = null): array
    {
        $dim = $dimension ?? $this->getDimension();
        $normalized = mb_strtolower(trim($text), 'UTF-8');
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        $vector = [];
        $round = 0;

        while (count($vector) < $dim) {
            $hash = hash('sha256', $normalized . '|round:' . $round);
            // 64 hex characters = 16 4-char chunks
            for ($i = 0; $i < 64 && count($vector) < $dim; $i += 4) {
                $hexChunk = substr($hash, $i, 4);
                $val = (int) hexdec($hexChunk); // 0 to 65535
                // Map uniformly to [-1.0, 1.0]
                $vector[] = ($val / 32767.5) - 1.0;
            }
            $round++;
        }

        return $this->normalizeVector($vector);
    }

    /**
     * Call Google Gemini Embedding API (text-embedding-004).
     *
     * @param string $text
     * @param string $apiKey
     * @param int $dimension
     * @return array<int, float>|null
     */
    protected function embedWithGemini(string $text, string $apiKey, int $dimension): ?array
    {
        try {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/text-embedding-004:embedContent?key=' . urlencode($apiKey);

            $payload = [
                'model' => 'models/text-embedding-004',
                'content' => [
                    'parts' => [
                        ['text' => $text],
                    ],
                ],
            ];

            if ($dimension <= 768) {
                $payload['outputDimensionality'] = $dimension;
            }

            $response = Http::timeout(10)
                ->acceptJson()
                ->post($url, $payload);

            if ($response->successful()) {
                $values = $response->json('embedding.values');
                if (is_array($values) && !empty($values)) {
                    return $this->adjustDimension($values, $dimension);
                }
            }

            Log::warning('EmbeddingService: Gemini API returned non-success response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('EmbeddingService: Gemini API call failed, falling back to deterministic embedding', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Call OpenAI Embedding API (text-embedding-3-small).
     *
     * @param string $text
     * @param string $apiKey
     * @param int $dimension
     * @return array<int, float>|null
     */
    protected function embedWithOpenAi(string $text, string $apiKey, int $dimension): ?array
    {
        try {
            $url = 'https://api.openai.com/v1/embeddings';

            $payload = [
                'model' => 'text-embedding-3-small',
                'input' => $text,
                'dimensions' => $dimension,
            ];

            $response = Http::timeout(10)
                ->withToken($apiKey)
                ->acceptJson()
                ->post($url, $payload);

            if ($response->successful()) {
                $values = $response->json('data.0.embedding');
                if (is_array($values) && !empty($values)) {
                    return $this->adjustDimension($values, $dimension);
                }
            }

            Log::warning('EmbeddingService: OpenAI API returned non-success response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('EmbeddingService: OpenAI API call failed, falling back to deterministic embedding', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Normalize vector to unit length (L2 norm = 1.0).
     *
     * @param array<int, float|int> $vector
     * @return array<int, float>
     */
    public function normalizeVector(array $vector): array
    {
        $sumSquares = 0.0;
        foreach ($vector as $val) {
            $v = (float) $val;
            $sumSquares += $v * $v;
        }

        $norm = sqrt($sumSquares);
        if ($norm <= 0.0) {
            return array_map(fn($v) => (float) $v, $vector);
        }

        $normalized = [];
        foreach ($vector as $val) {
            $normalized[] = (float) $val / $norm;
        }

        return $normalized;
    }

    /**
     * Adjust vector size to target dimension and re-normalize.
     *
     * @param array<int, mixed> $vector
     * @param int $targetDimension
     * @return array<int, float>
     */
    protected function adjustDimension(array $vector, int $targetDimension): array
    {
        $currentCount = count($vector);
        if ($currentCount === $targetDimension) {
            return $this->normalizeVector($vector);
        }

        if ($currentCount > $targetDimension) {
            return $this->normalizeVector(array_slice($vector, 0, $targetDimension));
        }

        // If current count < target, pad deterministically and re-normalize
        $padded = $vector;
        $round = 0;
        while (count($padded) < $targetDimension) {
            $hash = hash('sha256', 'pad|' . $round . '|' . ($vector[$round % $currentCount] ?? 0));
            for ($i = 0; $i < 64 && count($padded) < $targetDimension; $i += 4) {
                $chunk = substr($hash, $i, 4);
                $val = (int) hexdec($chunk);
                $padded[] = ($val / 32767.5) - 1.0;
            }
            $round++;
        }

        return $this->normalizeVector($padded);
    }
}
