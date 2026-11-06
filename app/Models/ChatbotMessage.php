<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    use HasFactory;

    protected $table = 'chatbot_messages';

    /**
     * ChatbotMessage bersifat append-only log, menggunakan created_at tanpa updated_at.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Atribut yang dapat diisi secara mass-assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tool_calls',
        'tool_call_id',
        'name',
        'citations',
        'prompt_tokens',
        'completion_tokens',
        'latency_ms',
        'feedback_rating',
        'created_at',
    ];

    /**
     * Atribut casting tipe data.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tool_calls' => 'array',
            'citations' => 'array',
            'created_at' => 'datetime',
            'prompt_tokens' => 'integer',
            'completion_tokens' => 'integer',
            'latency_ms' => 'integer',
            'feedback_rating' => 'integer',
        ];
    }

    /**
     * Otomatis mengisi created_at saat pembuatan record jika belum disetel.
     */
    protected static function booted(): void
    {
        static::creating(function (self $message): void {
            if (empty($message->created_at)) {
                $message->created_at = now();
            }
        });
    }

    /**
     * Relasi ke sesi percakapan induk (ChatbotConversation).
     *
     * @return BelongsTo<ChatbotConversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatbotConversation::class, 'conversation_id');
    }
}
