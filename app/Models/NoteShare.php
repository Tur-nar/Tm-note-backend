<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'note_id',
        'owner_id',
        'shared_with_id',
        'shared_email',
        'permission',
        'status',
        'invite_token',
        'accepted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    // ── Relationships ──

    public function note()
    {
        return $this->belongsTo(Note::class);
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function sharedWith()
    {
        return $this->belongsTo(User::class, 'shared_with_id');
    }

    // ── Query Scopes ──

    /**
     * Only accepted shares.
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Only pending invitations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Shares for a specific user (as recipient).
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('shared_with_id', $userId);
    }

    /**
     * Non-expired shares (null expires_at = never expires).
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
}
