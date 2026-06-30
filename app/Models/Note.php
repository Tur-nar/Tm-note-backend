<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Note extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'content_format',
        'color',
        'is_pinned',
        'archived_at',
        'x_position',
        'y_position',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
            'x_position' => 'float',
            'y_position' => 'float',
        ];
    }

    // ── Relationships ──

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function outgoingLinks()
    {
        return $this->hasMany(NoteLink::class, 'source_note_id');
    }

    public function incomingLinks()
    {
        return $this->hasMany(NoteLink::class, 'target_note_id');
    }

    public function shares()
    {
        return $this->hasMany(NoteShare::class);
    }

    public function sharedUsers()
    {
        return $this->belongsToMany(User::class, 'note_shares', 'note_id', 'shared_with_id')
                    ->withPivot('permission', 'status')
                    ->wherePivot('status', 'accepted');
    }

    // ── Query Scopes ──

    /**
     * Full-text search across title and content.
     * Uses FULLTEXT index for speed, with LIKE fallback for partial matches.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->whereFullText(['title', 'content'], $term)
                ->orWhere('title', 'like', "%{$term}%");
        });
    }

    /**
     * Filter: apply search + sort from query params.
     */
    public function scopeFilter($query, array $filters)
    {
        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $sortBy = $filters['sortBy'] ?? 'updated_at';
        $sortDir = $filters['sortDir'] ?? 'desc';
        $query->orderBy($sortBy, $sortDir);
    }

    /**
     * Only non-archived notes.
     */
    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Only archived notes.
     */
    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }
}
