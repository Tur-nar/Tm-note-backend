<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'content_format',
        'color',
        'is_pinned',
        'archived_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned'   => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Query Scopes ──

    /**
     * Full-text search across title and content.
     */
    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('content', 'like', "%{$term}%");
        });
    }

    /**
     * Filter: apply search + sort from query params.
     */
    public function scopeFilter($query, array $filters)
    {
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        $sortBy  = $filters['sortBy']  ?? 'updated_at';
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
