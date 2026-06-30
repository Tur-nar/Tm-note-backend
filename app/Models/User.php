<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function noteLinks()
    {
        return $this->hasMany(NoteLink::class);
    }

    // ── Sharing Relationships ──

    /**
     * Notes shared WITH this user (accepted invitations only).
     */
    public function sharedNotes()
    {
        return $this->belongsToMany(Note::class, 'note_shares', 'shared_with_id', 'note_id')
                    ->withPivot('permission', 'status', 'owner_id')
                    ->wherePivot('status', 'accepted');
    }

    /**
     * Note share invitations sent BY this user.
     */
    public function sentNoteShares()
    {
        return $this->hasMany(NoteShare::class, 'owner_id');
    }

    /**
     * Note share invitations received BY this user.
     */
    public function receivedNoteShares()
    {
        return $this->hasMany(NoteShare::class, 'shared_with_id');
    }

    /**
     * Canvas share invitations sent BY this user.
     */
    public function sentCanvasShares()
    {
        return $this->hasMany(CanvasShare::class, 'owner_id');
    }

    /**
     * Canvas share invitations received BY this user.
     */
    public function receivedCanvasShares()
    {
        return $this->hasMany(CanvasShare::class, 'shared_with_id');
    }

    /**
     * Check if user can access a note (owner or accepted collaborator).
     * Used for broadcast channel auth.
     */
    public function canViewNote(int $noteId): bool
    {
        // Owner check
        if ($this->notes()->where('id', $noteId)->exists()) {
            return true;
        }

        // Collaborator check
        return NoteShare::where('note_id', $noteId)
            ->where('shared_with_id', $this->id)
            ->where('status', 'accepted')
            ->exists();
    }
}
