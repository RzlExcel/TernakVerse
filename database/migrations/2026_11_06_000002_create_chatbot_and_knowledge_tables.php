<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isPgsql = DB::getDriverName() === 'pgsql';
        $hasVector = false;

        // 1. Deteksi Adaptif Ekstensi pgvector di PostgreSQL
        if ($isPgsql) {
            try {
                $available = DB::select("SELECT 1 FROM pg_available_extensions WHERE name = 'vector'");
                if (! empty($available)) {
                    DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');
                    $hasVector = true;
                }
            } catch (\Throwable) {
                $hasVector = false;
            }
        }

        // 2. Tabel Master Dokumen Pengetahuan (Knowledge Documents)
        Schema::create('knowledge_documents', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('source_type', 50)->index();
            $table->string('author', 150)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('file_hash', 64)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->smallInteger('published_year')->nullable();
            $table->timestamps();
        });

        // 3. Tabel Potongan Vektor Dokumen (Knowledge Chunks)
        Schema::create('knowledge_chunks', function (Blueprint $table) use ($isPgsql, $hasVector): void {
            $table->id();
            $table->foreignId('document_id')->constrained('knowledge_documents')->cascadeOnDelete();
            $table->integer('chunk_index');
            $table->text('content');
            $table->integer('token_count')->default(0);

            if ($isPgsql) {
                $table->jsonb('metadata')->nullable()->default('{}');
            } else {
                $table->json('metadata')->nullable()->default('{}');
            }

            // Fallback skema jika pgvector tidak aktif atau driver SQLite
            if (! $hasVector) {
                if ($isPgsql) {
                    $table->jsonb('embedding')->nullable();
                } else {
                    $table->text('embedding')->nullable();
                }
            }

            $table->timestamps();
        });

        // 3a. Kolom Vector & Indeks HNSW jika pgvector aktif
        if ($hasVector) {
            DB::statement('ALTER TABLE knowledge_chunks ADD COLUMN embedding vector(1536);');
            DB::statement('
                CREATE INDEX idx_knowledge_chunks_embedding_hnsw 
                ON knowledge_chunks 
                USING hnsw (embedding vector_cosine_ops)
                WITH (m = 16, ef_construction = 64);
            ');
        }

        // 3b. GIN Index untuk pencarian metadata jsonb pada PostgreSQL
        if ($isPgsql) {
            DB::statement('CREATE INDEX idx_knowledge_chunks_metadata_gin ON knowledge_chunks USING gin (metadata);');
        }

        // 4. Tabel Sesi Percakapan Chatbot
        Schema::create('chatbot_conversations', function (Blueprint $table) use ($isPgsql): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_token', 80)->index();
            $table->string('title')->default('Percakapan Baru');
            $table->timestamp('last_active_at')->index();

            if ($isPgsql) {
                $table->jsonb('metadata')->nullable();
            } else {
                $table->json('metadata')->nullable();
            }

            $table->timestamps();
        });

        // 5. Tabel Pesan Percakapan Chatbot
        Schema::create('chatbot_messages', function (Blueprint $table) use ($isPgsql): void {
            $table->id();
            $table->foreignUuid('conversation_id')->constrained('chatbot_conversations')->cascadeOnDelete();
            $table->string('role', 20)->index();
            $table->text('content')->nullable();

            if ($isPgsql) {
                $table->jsonb('tool_calls')->nullable();
            } else {
                $table->json('tool_calls')->nullable();
            }

            $table->string('tool_call_id', 100)->nullable()->index();
            $table->string('name', 100)->nullable();

            if ($isPgsql) {
                $table->jsonb('citations')->nullable();
            } else {
                $table->json('citations')->nullable();
            }

            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('latency_ms')->default(0);
            $table->smallInteger('feedback_rating')->default(0);
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_conversations');
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('knowledge_documents');

        if (DB::getDriverName() === 'pgsql') {
            try {
                DB::statement('DROP EXTENSION IF EXISTS vector CASCADE;');
            } catch (\Throwable) {
                // Abaikan jika ekstensi vector masih digunakan oleh tabel lain atau hak akses terbatas
            }
        }
    }
};
