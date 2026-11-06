<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatbotChatRequest;
use App\Models\ChatbotConversation;
use App\Models\ChatbotMessage;
use App\Services\Chatbot\AgentOrchestratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatbotApiController extends Controller
{
    /**
     * Handle incoming streaming chat conversation via Server-Sent Events (SSE).
     */
    public function chat(ChatbotChatRequest $request, AgentOrchestratorService $orchestrator): StreamedResponse
    {
        $validated = $request->validated();
        $message = (string) $validated['message'];
        $conversationId = ! empty($validated['conversation_id']) ? (string) $validated['conversation_id'] : null;
        $sessionToken = ! empty($validated['session_token']) ? (string) $validated['session_token'] : Str::random(32);
        $provider = ! empty($validated['provider']) ? (string) $validated['provider'] : null;
        $user = $request->user();

        $headers = [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ];

        return response()->stream(function () use ($orchestrator, $message, $conversationId, $sessionToken, $user, $provider): void {
            try {
                $stream = $orchestrator->handleChatStream(
                    userMessage: $message,
                    conversationId: $conversationId,
                    sessionToken: $sessionToken,
                    user: $user,
                    provider: $provider,
                );

                foreach ($stream as $item) {
                    if (connection_aborted()) {
                        break;
                    }

                    echo 'event: '.$item['event']."\n";
                    echo 'data: '.json_encode($item['data'], JSON_UNESCAPED_UNICODE)."\n\n";

                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            } catch (\Throwable $e) {
                echo "event: error\n";
                $errorMessage = config('app.debug') ? $e->getMessage() : 'Terjadi gangguan internal pada server asisten.';
                echo 'data: '.json_encode([
                    'message' => 'Terjadi kesalahan pada layanan asisten cerdas.',
                    'error' => $errorMessage,
                ], JSON_UNESCAPED_UNICODE)."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        }, 200, $headers);
    }

    /**
     * Store user feedback rating on a specific chatbot message.
     */
    public function feedback(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message_id' => ['required', 'integer'],
            'rating' => ['required', 'integer', 'in:-1,0,1'],
            'session_token' => ['nullable', 'string'],
        ]);

        $message = ChatbotMessage::with('conversation')->find($validated['message_id']);
        if (! $message || ! $message->conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pesan percakapan tidak ditemukan.',
            ], 404);
        }

        $user = $request->user();
        $sessionToken = (string) ($validated['session_token'] ?? $request->input('session_token') ?? $request->header('X-Session-Token') ?? '');
        $conversation = $message->conversation;

        $isOwned = ($sessionToken !== '' && hash_equals((string) $conversation->session_token, $sessionToken))
            || ($user && (int) $conversation->user_id === (int) $user->id);

        if (! $isOwned) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak untuk memberikan umpan balik pada pesan ini.',
            ], 403);
        }

        $message->update([
            'feedback_rating' => $validated['rating'],
        ]);

        return response()->json([
            'status' => 'success',
        ]);
    }

    /**
     * Retrieve conversation history along with its messages.
     */
    public function history(Request $request, string $conversationId): JsonResponse
    {
        $conversation = ChatbotConversation::with('messages')->find($conversationId);

        if (! $conversation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Percakapan tidak ditemukan.',
            ], 404);
        }

        $user = $request->user();
        $sessionToken = (string) ($request->input('session_token') ?? $request->header('X-Session-Token') ?? '');

        $isOwned = ($sessionToken !== '' && hash_equals((string) $conversation->session_token, $sessionToken))
            || ($user && (int) $conversation->user_id === (int) $user->id);

        if (! $isOwned) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak ke riwayat percakapan ini.',
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'conversation' => $conversation,
        ]);
    }
}
