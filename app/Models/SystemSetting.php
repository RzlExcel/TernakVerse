<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class SystemSetting extends Model
{
    use HasFactory;

    protected $table = 'system_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
        'is_encrypted',
        'updated_by',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'typed_value',
    ];

    /**
     * Relationship to the user who last updated this setting.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Accessor for typed_value.
     */
    public function getTypedValueAttribute(): mixed
    {
        return $this->getTypedValue();
    }

    /**
     * Get typed value according to the type and is_encrypted flag.
     */
    public function getTypedValue(): mixed
    {
        if ($this->value === null) {
            return null;
        }

        $raw = $this->value;
        if ($this->is_encrypted) {
            try {
                $raw = Crypt::decryptString($raw);
            } catch (Throwable) {
                return $raw;
            }
        }

        return match ($this->type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $raw,
            'float' => (float) $raw,
            'json' => json_decode($raw, true) ?? [],
            default => $raw,
        };
    }

    /**
     * Set typed value according to type and is_encrypted flag.
     */
    public function setTypedValue(mixed $value): void
    {
        if ($value === null) {
            $this->value = null;

            return;
        }

        $serialized = match ($this->type) {
            'boolean' => $value ? '1' : '0',
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };

        if ($this->is_encrypted) {
            $this->value = Crypt::encryptString($serialized);
        } else {
            $this->value = $serialized;
        }
    }

    /**
     * Helper to retrieve a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        $val = $setting->getTypedValue();

        return $val ?? $default;
    }

    /**
     * Helper to store or update a setting.
     */
    public static function set(
        string $key,
        mixed $value,
        ?string $group = null,
        ?string $type = null,
        ?string $description = null,
        ?bool $isEncrypted = null,
        ?int $updatedBy = null
    ): self {
        $setting = static::firstOrNew(['key' => $key]);

        if ($group !== null) {
            $setting->group = $group;
        } elseif (! $setting->exists && empty($setting->group)) {
            $setting->group = 'general';
        }

        if ($type !== null) {
            $setting->type = $type;
        } elseif (! $setting->exists && empty($setting->type)) {
            $setting->type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                is_array($value) => 'json',
                default => 'string',
            };
        }

        if ($description !== null) {
            $setting->description = $description;
        }

        if ($isEncrypted !== null) {
            $setting->is_encrypted = $isEncrypted;
        }

        if ($updatedBy !== null) {
            $setting->updated_by = $updatedBy;
        }

        $setting->setTypedValue($value);
        $setting->save();

        return $setting;
    }
}
