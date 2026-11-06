<?php

declare(strict_types=1);

namespace Tests\Feature\Chatbot;

use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Models\User;
use App\Services\Chatbot\AgentOrchestratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ChatbotApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        DB::purge();

        if (! Schema::hasTable('users')) {
            Schema::create('users', function ($table): void {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->string('password')->nullable();
                $table->string('role')->default('peternak');
                $table->boolean('aktif')->default(true);
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('chatbot_conversations')) {
            Schema::create('chatbot_conversations', function ($table): void {
                $table->uuid('id')->primary();
                $table->foreignId('user_id')->nullable();
                $table->string('session_token', 80)->index();
                $table->string('title')->default('Percakapan Baru');
                $table->timestamp('last_active_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('chatbot_messages')) {
            Schema::create('chatbot_messages', function ($table): void {
                $table->id();
                $table->uuid('conversation_id');
                $table->string('role', 20);
                $table->text('content')->nullable();
                $table->json('tool_calls')->nullable();
                $table->string('tool_call_id', 100)->nullable();
                $table->string('name', 100)->nullable();
                $table->json('citations')->nullable();
                $table->integer('prompt_tokens')->default(0);
                $table->integer('completion_tokens')->default(0);
                $table->integer('latency_ms')->default(0);
                $table->smallInteger('feedback_rating')->default(0);
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    /**
     * POST /api/v1/chatbot/chat rejects empty message input with 422 Unprocessable Entity.
     */
    public function test_chat_endpoint_validates_message_input_rejecting_empty_message_with_422(): void
    {
        $response = $this->postJson('/api/v1/chatbot/chat', [
            'message' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /**
     * POST /api/v1/chatbot/chat returns 200 with text/event-stream headers.
     */
    public function test_chat_endpoint_returns_200_with_event_stream_headers(): void
    {
        $mockOrchestrator = $this->createMock(AgentOrchestratorService::class);
        $mockOrchestrator->method('handleChatStream')->willReturnCallback(function () {
            yield ['event' => 'metadata', 'data' => ['conversation_id' => (string) Str::uuid(), 'session_token' => 'tok_1', 'message_id' => 1, 'model' => 'simulation']];
            yield ['event' => 'chunk', 'data' => ['content' => 'Halo dari Asisten TernakVerse.']];
            yield ['event' => 'done', 'data' => ['status' => 'completed', 'conversation_id' => (string) Str::uuid(), 'message_id' => 2, 'triage_level' => 'MONITORING', 'latency_ms' => 25]];
        });
        $this->app->instance(AgentOrchestratorService::class, $mockOrchestrator);

        $response = $this->postJson('/api/v1/chatbot/chat', [
            'message' => 'Bagaimana situasi kesehatan hewan di Banyumas?',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('text/event-stream', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));
    }

    /**
     * POST /api/v1/chatbot/chat streams valid SSE formatted events (metadata, chunk, done).
     */
    public function test_chat_endpoint_streams_valid_sse_formatted_events(): void
    {
        $convId = (string) Str::uuid();
        $sessionToken = Str::random(32);

        $mockOrchestrator = $this->createMock(AgentOrchestratorService::class);
        $mockOrchestrator->method('handleChatStream')->willReturnCallback(function () use ($convId, $sessionToken) {
            yield [
                'event' => 'metadata',
                'data' => [
                    'conversation_id' => $convId,
                    'session_token' => $sessionToken,
                    'message_id' => 101,
                    'model' => 'simulation',
                ],
            ];
            yield [
                'event' => 'chunk',
                'data' => [
                    'content' => 'Populasi ternak sapi potong di Banyumas stabil.',
                ],
            ];
            yield [
                'event' => 'done',
                'data' => [
                    'status' => 'completed',
                    'conversation_id' => $convId,
                    'message_id' => 102,
                    'triage_level' => 'MONITORING',
                    'latency_ms' => 34,
                ],
            ];
        });
        $this->app->instance(AgentOrchestratorService::class, $mockOrchestrator);

        $response = $this->postJson('/api/v1/chatbot/chat', [
            'message' => 'Cek populasi sapi Banyumas',
            'session_token' => $sessionToken,
        ]);

        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('event: metadata', $content);
        $this->assertStringContainsString('"conversation_id":"' . $convId . '"', $content);
        $this->assertStringContainsString('event: chunk', $content);
        $this->assertStringContainsString('Populasi ternak sapi potong', $content);
        $this->assertStringContainsString('event: done', $content);
        $this->assertStringContainsString('"status":"completed"', $content);
    }

    /**
     * POST /api/v1/chatbot/chat throttles excessive requests beyond the rate limit.
     */
    public function test_chat_endpoint_rate_limiting_throttles_excessive_requests(): void
    {
        $mockOrchestrator = $this->createMock(AgentOrchestratorService::class);
        $mockOrchestrator->method('handleChatStream')->willReturnCallback(function () {
            yield ['event' => 'metadata', 'data' => ['conversation_id' => 'c1', 'session_token' => 's1', 'message_id' => 1, 'model' => 'mock']];
            yield ['event' => 'done', 'data' => ['status' => 'completed']];
        });
        $this->app->instance(AgentOrchestratorService::class, $mockOrchestrator);

        // Throttle on route is 60 requests per minute
        $lastStatus = 200;
        for ($i = 0; $i < 61; $i++) {
            $resp = $this->postJson('/api/v1/chatbot/chat', [
                'message' => 'Ping ' . $i,
            ]);
            $lastStatus = $resp->getStatusCode();
            if ($lastStatus === 429) {
                break;
            }
        }

        $this->assertSame(429, $lastStatus, 'Expected rate limiter to throttle request with HTTP 429');
    }

    /**
     * POST /api/v1/chatbot/feedback persists rating for an authorized session token.
     */
    public function test_feedback_endpoint_persists_rating_for_authorized_session_token(): void
    {
        $sessionToken = Str::random(32);
        $convId = (string) Str::uuid();

        ChatbotConversation::create([
            'id' => $convId,
            'session_token' => $sessionToken,
            'title' => 'Diskusi PMK',
            'last_active_at' => now(),
        ]);

        $message = ChatbotMessage::create([
            'conversation_id' => $convId,
            'role' => 'assistant',
            'content' => 'Vaksinasi PMK tahap 2 telah dimulai di Banyumas.',
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/chatbot/feedback', [
            'message_id' => $message->id,
            'rating' => 1,
            'session_token' => $sessionToken,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $message->refresh();
        $this->assertSame(1, (int) $message->feedback_rating);
    }

    /**
     * POST /api/v1/chatbot/feedback persists negative rating for authenticated conversation owner.
     */
    public function test_feedback_endpoint_persists_negative_rating_for_authenticated_owner(): void
    {
        $user = new User([
            'id' => 42,
            'name' => 'Peternak Banyumas',
            'email' => 'peternak@banyumas.go.id',
            'role' => 'peternak',
            'aktif' => true,
        ]);

        $convId = (string) Str::uuid();
        ChatbotConversation::create([
            'id' => $convId,
            'user_id' => $user->id,
            'session_token' => Str::random(32),
            'title' => 'Pertanyaan Kasus',
            'last_active_at' => now(),
        ]);

        $message = ChatbotMessage::create([
            'conversation_id' => $convId,
            'role' => 'assistant',
            'content' => 'Informasi kasus penyakit LSD di Wangon.',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/v1/chatbot/feedback', [
            'message_id' => $message->id,
            'rating' => -1,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
            ]);

        $message->refresh();
        $this->assertSame(-1, (int) $message->feedback_rating);
    }

    /**
     * POST /api/v1/chatbot/feedback returns 403 for unauthorized message access (BOLA/IDOR protection).
     */
    public function test_feedback_endpoint_returns_403_for_unauthorized_bola_idor_access(): void
    {
        $convId = (string) Str::uuid();
        ChatbotConversation::create([
            'id' => $convId,
            'session_token' => 'owner_token_abc',
            'title' => 'Diskusi Rahasia',
            'last_active_at' => now(),
        ]);

        $message = ChatbotMessage::create([
            'conversation_id' => $convId,
            'role' => 'assistant',
            'content' => 'Data rahasia operasional.',
            'created_at' => now(),
        ]);

        // Attempt feedback with a different session token
        $response = $this->postJson('/api/v1/chatbot/feedback', [
            'message_id' => $message->id,
            'rating' => 1,
            'session_token' => 'attacker_token_xyz',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
            ]);
    }

    /**
     * POST /api/v1/chatbot/feedback returns 404 when message_id does not exist.
     */
    public function test_feedback_endpoint_returns_404_for_non_existent_message(): void
    {
        $response = $this->postJson('/api/v1/chatbot/feedback', [
            'message_id' => 999999,
            'rating' => 1,
            'session_token' => 'any_token',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Pesan percakapan tidak ditemukan.',
            ]);
    }

    /**
     * GET /api/v1/chatbot/history/{conversationId} returns 200 with message history when session_token matches.
     */
    public function test_history_endpoint_returns_200_with_message_history_when_session_token_matches(): void
    {
        $convId = (string) Str::uuid();
        $sessionToken = Str::random(32);

        $conversation = ChatbotConversation::create([
            'id' => $convId,
            'session_token' => $sessionToken,
            'title' => 'SOP Pengendalian PMK',
            'last_active_at' => now(),
        ]);

        ChatbotMessage::create([
            'conversation_id' => $convId,
            'role' => 'user',
            'content' => 'Bagaimana desinfeksi kandang PMK?',
            'created_at' => now()->subMinute(),
        ]);

        ChatbotMessage::create([
            'conversation_id' => $convId,
            'role' => 'assistant',
            'content' => 'Gunakan larutan asam sitrat 0.2% atau natrium hipoklorit.',
            'created_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/chatbot/history/{$convId}?session_token={$sessionToken}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'conversation' => [
                    'id' => $convId,
                    'title' => 'SOP Pengendalian PMK',
                ],
            ]);

        $responseData = $response->json();
        $this->assertCount(2, $responseData['conversation']['messages']);
    }

    /**
     * GET /api/v1/chatbot/history/{conversationId} returns 403 when session_token does not match.
     */
    public function test_history_endpoint_returns_403_when_session_token_does_not_match(): void
    {
        $convId = (string) Str::uuid();
        ChatbotConversation::create([
            'id' => $convId,
            'session_token' => 'legit_session_123',
            'title' => 'Konsultasi Gejala Sapi',
            'last_active_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/chatbot/history/{$convId}?session_token=fraudulent_session_999");

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Akses ditolak ke riwayat percakapan ini.',
            ]);
    }

    /**
     * GET /api/v1/chatbot/history/{conversationId} returns 404 when conversation does not exist.
     */
    public function test_history_endpoint_returns_404_when_conversation_not_found(): void
    {
        $nonExistentId = (string) Str::uuid();
        $response = $this->getJson("/api/v1/chatbot/history/{$nonExistentId}?session_token=any_token");

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Percakapan tidak ditemukan.',
            ]);
    }
}
