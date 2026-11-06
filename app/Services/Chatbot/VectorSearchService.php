<?php

declare(strict_types=1);

namespace App\Services\Chatbot;

use App\Models\KnowledgeChunk;
use App\Models\KnowledgeDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Service to execute semantic vector similarity searches over the knowledge base
 * with PostgreSQL pgvector HNSW acceleration and adaptive fallback for SQLite/tests.
 */
class VectorSearchService
{
    /**
     * Create a new VectorSearchService instance.
     */
    public function __construct(
        protected EmbeddingService $embeddingService
    ) {}

    /**
     * Search relevant knowledge chunks using vector cosine distance and adaptive fallback.
     *
     * @param string $query User query text to search
     * @param int $limit Maximum number of results to retrieve
     * @param array<string, mixed> $filters Metadata filter criteria (e.g. kategori_penyakit, sumber, spesies_terkait)
     * @return Collection<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 5, array $filters = []): Collection
    {
        if (!Schema::hasTable('knowledge_chunks')) {
            Log::info('VectorSearchService: knowledge_chunks table does not exist.');
            return collect();
        }

        $queryEmbedding = $this->embeddingService->generateEmbedding($query);

        if ($this->isPgVectorAvailable()) {
            try {
                return $this->searchWithPgVector($queryEmbedding, $limit, $filters);
            } catch (\Throwable $e) {
                Log::warning('VectorSearchService: pgvector search failed, falling back to adaptive search', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->searchAdaptive($query, $queryEmbedding, $limit, $filters);
    }

    /**
     * Check if PostgreSQL pgvector extension and vector column are active.
     */
    protected function isPgVectorAvailable(): bool
    {
        try {
            if (DB::connection()->getDriverName() !== 'pgsql') {
                return false;
            }

            if (!Schema::hasTable('knowledge_chunks') || !Schema::hasColumn('knowledge_chunks', 'embedding')) {
                return false;
            }

            $extension = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'vector' LIMIT 1");
            return !empty($extension);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Perform pgvector cosine distance search using the <=> operator.
     *
     * @param array<int, float> $queryEmbedding
     * @param int $limit
     * @param array<string, mixed> $filters
     * @return Collection<int, array<string, mixed>>
     */
    protected function searchWithPgVector(array $queryEmbedding, int $limit, array $filters): Collection
    {
        $vectorLiteral = '[' . implode(',', $queryEmbedding) . ']';

        if (class_exists(KnowledgeChunk::class)) {
            $queryBuilder = KnowledgeChunk::query();
            if (method_exists(KnowledgeChunk::class, 'document')) {
                $queryBuilder->with('document');
            }

            foreach ($filters as $key => $value) {
                if ($value !== null && $value !== '') {
                    $queryBuilder->where("metadata->{$key}", $value);
                }
            }

            // Cosine similarity score = 1 - (embedding <=> target_vector)
            $queryBuilder->selectRaw('knowledge_chunks.*, (1 - (embedding <=> ?::vector)) as search_score', [$vectorLiteral])
                ->orderByRaw('embedding <=> ?::vector ASC', [$vectorLiteral])
                ->limit($limit);

            $records = $queryBuilder->get();
            return $this->formatResults($records);
        }

        // Direct database query fallback
        $queryBuilder = DB::table('knowledge_chunks');
        if (Schema::hasTable('knowledge_documents')) {
            $queryBuilder->leftJoin('knowledge_documents', 'knowledge_chunks.document_id', '=', 'knowledge_documents.id')
                ->select(
                    'knowledge_chunks.*',
                    'knowledge_documents.title as doc_title',
                    'knowledge_documents.author as doc_author',
                    'knowledge_documents.published_year as doc_published_year'
                );
        } else {
            $queryBuilder->select('knowledge_chunks.*');
        }

        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                $queryBuilder->where("knowledge_chunks.metadata->{$key}", $value);
            }
        }

        $queryBuilder->selectRaw('(1 - (knowledge_chunks.embedding <=> ?::vector)) as search_score', [$vectorLiteral])
            ->orderByRaw('knowledge_chunks.embedding <=> ?::vector ASC', [$vectorLiteral])
            ->limit($limit);

        $records = $queryBuilder->get();
        return $this->formatResults($records);
    }

    /**
     * Perform adaptive search using PHP cosine similarity calculation or keyword overlap.
     *
     * @param string $query
     * @param array<int, float> $queryEmbedding
     * @param int $limit
     * @param array<string, mixed> $filters
     * @return Collection<int, array<string, mixed>>
     */
    protected function searchAdaptive(string $query, array $queryEmbedding, int $limit, array $filters): Collection
    {
        if (class_exists(KnowledgeChunk::class)) {
            $queryBuilder = KnowledgeChunk::query();
            if (method_exists(KnowledgeChunk::class, 'document')) {
                $queryBuilder->with('document');
            }
        } else {
            $queryBuilder = DB::table('knowledge_chunks');
            if (Schema::hasTable('knowledge_documents')) {
                $queryBuilder->leftJoin('knowledge_documents', 'knowledge_chunks.document_id', '=', 'knowledge_documents.id')
                    ->select(
                        'knowledge_chunks.*',
                        'knowledge_documents.title as doc_title',
                        'knowledge_documents.author as doc_author',
                        'knowledge_documents.published_year as doc_published_year'
                    );
            }
        }

        try {
            foreach ($filters as $key => $value) {
                if ($value !== null && $value !== '') {
                    $queryBuilder->where("metadata->{$key}", $value);
                }
            }
        } catch (\Throwable) {
            // Driver may not support json path query; will filter in PHP below
        }

        $chunks = $queryBuilder->get();
        $scoredChunks = [];

        foreach ($chunks as $chunk) {
            $metadata = $this->extractMetadata($chunk);

            if (!$this->matchesFilters($metadata, $filters)) {
                continue;
            }

            $chunkEmbedding = $this->extractEmbedding($chunk);

            if (!empty($chunkEmbedding)) {
                $score = $this->embeddingService->cosineSimilarity($queryEmbedding, $chunkEmbedding);
            } else {
                $score = $this->calculateKeywordScore($query, (string) ($chunk->content ?? ''), $metadata);
            }

            $chunk->search_score = $score;
            $scoredChunks[] = $chunk;
        }

        usort($scoredChunks, function ($a, $b) {
            $scoreA = (float) ($a->search_score ?? 0.0);
            $scoreB = (float) ($b->search_score ?? 0.0);
            return $scoreB <=> $scoreA;
        });

        $topChunks = array_slice($scoredChunks, 0, $limit);

        return $this->formatResults(collect($topChunks));
    }

    /**
     * Extract metadata array from chunk model or DB record.
     *
     * @param mixed $chunk
     * @return array<string, mixed>
     */
    protected function extractMetadata(mixed $chunk): array
    {
        $metadata = $chunk->metadata ?? [];
        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Extract embedding float array from chunk model or DB record.
     *
     * @param mixed $chunk
     * @return array<int, float>|null
     */
    protected function extractEmbedding(mixed $chunk): ?array
    {
        $embedding = $chunk->embedding ?? null;
        if (is_array($embedding) && !empty($embedding)) {
            return array_map(fn($v) => (float) $v, $embedding);
        }

        if (is_string($embedding)) {
            $trimmed = trim($embedding);
            if (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    return array_map(fn($v) => (float) $v, $decoded);
                }

                $inner = trim($trimmed, '[]');
                $parts = explode(',', $inner);
                return array_map(fn($v) => (float) trim($v), $parts);
            }
        }

        return null;
    }

    /**
     * Check if chunk metadata matches all given filter criteria.
     *
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $filters
     * @return bool
     */
    protected function matchesFilters(array $metadata, array $filters): bool
    {
        foreach ($filters as $key => $expectedValue) {
            if ($expectedValue === null || $expectedValue === '') {
                continue;
            }

            $actualValue = $metadata[$key] ?? null;
            if ($actualValue === null) {
                return false;
            }

            if (is_scalar($actualValue) && is_scalar($expectedValue)) {
                if (strcasecmp((string) $actualValue, (string) $expectedValue) !== 0) {
                    return false;
                }
            } elseif ($actualValue != $expectedValue) {
                return false;
            }
        }

        return true;
    }

    /**
     * Compute fallback keyword overlap score between query and chunk content.
     *
     * @param string $query
     * @param string $content
     * @param array<string, mixed> $metadata
     * @return float
     */
    protected function calculateKeywordScore(string $query, string $content, array $metadata): float
    {
        $tokens = array_filter(
            explode(' ', mb_strtolower((string) preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query))),
            fn(string $token) => mb_strlen($token) > 2
        );

        if (empty($tokens)) {
            return 0.1;
        }

        $corpus = mb_strtolower($content);
        foreach ($metadata as $metaValue) {
            if (is_scalar($metaValue)) {
                $corpus .= ' ' . mb_strtolower((string) $metaValue);
            }
        }

        $matched = 0;
        foreach ($tokens as $token) {
            if (str_contains($corpus, $token)) {
                $matched++;
            }
        }

        return round($matched / count($tokens), 4);
    }

    /**
     * Format retrieved chunk records into standardized response collection.
     *
     * @param iterable<mixed> $chunks
     * @return Collection<int, array<string, mixed>>
     */
    protected function formatResults(iterable $chunks): Collection
    {
        $collection = $chunks instanceof Collection ? $chunks : collect($chunks);

        return $collection->map(function ($chunk) {
            $metadata = $this->extractMetadata($chunk);

            $document = null;
            if (isset($chunk->document)) {
                $document = $chunk->document;
            } elseif (isset($chunk->document_id) && Schema::hasTable('knowledge_documents')) {
                $document = DB::table('knowledge_documents')->where('id', $chunk->document_id)->first();
            }

            $docTitle = $document?->title ?? ($chunk->doc_title ?? ($metadata['judul_dokumen'] ?? null));
            $docAuthor = $document?->author ?? ($chunk->doc_author ?? ($metadata['sumber'] ?? null));
            $docYear = $document?->published_year ?? ($chunk->doc_published_year ?? ($metadata['tahun'] ?? null));
            $category = $metadata['kategori_penyakit'] ?? null;

            $rawScore = $chunk->search_score ?? ($chunk->score ?? 0.0);
            $score = round((float) $rawScore, 4);

            return [
                'id' => $chunk->id,
                'chunk_index' => (int) ($chunk->chunk_index ?? 0),
                'content' => (string) ($chunk->content ?? ''),
                'metadata' => $metadata,
                'score' => $score,
                'citation' => [
                    'chunk_id' => $chunk->id,
                    'document_title' => $docTitle,
                    'source' => $docAuthor,
                    'published_year' => $docYear ? (int) $docYear : null,
                    'category' => $category,
                ],
            ];
        });
    }
}
