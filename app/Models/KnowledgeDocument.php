<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Master Dokumen Pengetahuan Veteriner & Regulasi TernakVerse.
 *
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $source_type
 * @property string|null $author
 * @property string|null $file_path
 * @property string $file_hash
 * @property bool $is_active
 * @property int|null $published_year
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, KnowledgeChunk> $chunks
 * @property-read int|null $chunks_count
 *
 * @method static Builder<KnowledgeDocument> active()
 */
class KnowledgeDocument extends Model
{
    use HasFactory;

    /**
     * Nama tabel di database.
     *
     * @var string
     */
    protected $table = 'knowledge_documents';

    /**
     * Kolom yang dapat diisi secara mass-assignment.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'slug',
        'source_type',
        'author',
        'file_path',
        'file_hash',
        'is_active',
        'published_year',
    ];

    /**
     * Definisi type casting untuk atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_year' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke potongan teks / vektor dokumen (KnowledgeChunk).
     *
     * @return HasMany<KnowledgeChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id');
    }

    /**
     * Scope query untuk hanya mengambil dokumen yang aktif.
     *
     * @param  Builder<KnowledgeDocument>  $query
     * @return Builder<KnowledgeDocument>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
