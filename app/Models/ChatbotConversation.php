<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ChatbotConversation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'chatbot_conversations';

    /**
     * Tipe primary key berupa string UUID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Primary key tidak berformat auto-incrementing integer.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Atribut yang dapat diisi secara mass-assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'user_id',
        'session_token',
        'title',
        'last_active_at',
        'metadata',
    ];

    /**
     * Atribut casting tipe data.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'last_active_at' => 'datetime',
        ];
    }

    /**
     * Booted model generator untuk memastikan UUID terisi sebelum record dibuat.
     */
    protected static function booted(): void
    {
        static::creating(function (self $conversation): void {
            if (empty($conversation->id)) {
                $conversation->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Relasi ke seluruh pesan dalam sesi percakapan.
     *
     * @return HasMany<ChatbotMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatbotMessage::class, 'conversation_id');
    }

    /**
     * Relasi ke pengguna terdaftar pemilik percakapan.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
