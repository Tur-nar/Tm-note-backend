<?php

use App\Models\Note;
use App\Models\NoteShare;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Presence channels for real-time collaboration. Each channel requires
| user authentication and returns user metadata for the presence list.
|
*/

/**
 * User's private channel (for notifications).
 */
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * Presence channel for note editing.
 * Allows the note owner and accepted collaborators to join.
 * Returns user metadata for the presence avatar list.
 */
Broadcast::channel('note.{noteId}', function ($user, $noteId) {
    $note = Note::find($noteId);
    if (! $note) {
        return false;
    }

    // Owner
    if ($note->user_id === $user->id) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => 'owner',
        ];
    }

    // Accepted collaborator
    $share = NoteShare::where('note_id', $noteId)
        ->where('shared_with_id', $user->id)
        ->where('status', 'accepted')
        ->first();

    if ($share) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $share->permission,
        ];
    }

    return false;
});

/**
 * Presence channel for canvas workspace.
 * Any authenticated user can join their own canvas.
 * Returns user metadata for presence and live cursors.
 */
Broadcast::channel('canvas.main', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'role' => 'owner',
    ];
});
