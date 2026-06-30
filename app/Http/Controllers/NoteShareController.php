<?php

namespace App\Http\Controllers;

use App\Mail\ShareInvitationMail;
use App\Models\Note;
use App\Models\NoteShare;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NoteShareController extends Controller
{
    /**
     * GET /api/note-shares
     * List all shares sent BY the authenticated user (notes they've shared out).
     */
    public function index(Request $request)
    {
        $shares = $request->user()
            ->sentNoteShares()
            ->with(['note:id,title,color', 'sharedWith:id,name,email'])
            ->latest()
            ->get();

        return response()->json(['data' => $shares]);
    }

    /**
     * GET /api/note-shares/received
     * List notes shared WITH the current user (invitations + accepted shares).
     */
    public function sharedWithMe(Request $request)
    {
        $shares = NoteShare::where('shared_with_id', $request->user()->id)
            ->with(['note:id,title,color,content,content_format,updated_at', 'owner:id,name,email'])
            ->latest()
            ->get();

        return response()->json(['data' => $shares]);
    }

    /**
     * POST /api/note-shares
     * Share a note with an email. Creates invitation and sends email.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'note_id' => ['required', 'integer', 'exists:notes,id'],
            'email' => ['required', 'email', 'max:255'],
            'permission' => ['required', 'in:view,edit,admin'],
        ]);

        $note = Note::findOrFail($validated['note_id']);

        // Only the note owner can share
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You can only share notes you own.'], 403);
        }

        // Can't share with yourself
        if (strtolower($validated['email']) === strtolower($request->user()->email)) {
            return response()->json(['message' => 'You cannot share a note with yourself.'], 422);
        }

        // Check for duplicate share
        $existing = NoteShare::where('note_id', $note->id)
            ->where('shared_email', strtolower($validated['email']))
            ->first();

        if ($existing) {
            return response()->json(['message' => 'This note is already shared with that email.'], 409);
        }

        // Resolve the target user (if they're already registered)
        $targetUser = User::where('email', strtolower($validated['email']))->first();

        $share = NoteShare::create([
            'note_id' => $note->id,
            'owner_id' => $request->user()->id,
            'shared_with_id' => $targetUser?->id,
            'shared_email' => strtolower($validated['email']),
            'permission' => $validated['permission'],
            'status' => 'pending',
            'invite_token' => Str::random(64),
        ]);

        // Send invitation email
        Mail::to($validated['email'])->send(new ShareInvitationMail($share, 'note'));

        $share->load(['note:id,title,color', 'sharedWith:id,name,email']);

        return response()->json([
            'message' => 'Note shared successfully. Invitation sent.',
            'data' => $share,
        ], 201);
    }

    /**
     * PATCH /api/note-shares/{noteShare}
     * Update permission level (owner only).
     */
    public function update(Request $request, NoteShare $noteShare)
    {
        // Only the note owner can update permissions
        if ($noteShare->owner_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'permission' => ['required', 'in:view,edit,admin'],
        ]);

        $noteShare->update($validated);

        return response()->json([
            'message' => 'Permission updated.',
            'data' => $noteShare->fresh()->load(['note:id,title,color', 'sharedWith:id,name,email']),
        ]);
    }

    /**
     * DELETE /api/note-shares/{noteShare}
     * Revoke a share (owner) or leave a share (collaborator).
     */
    public function destroy(Request $request, NoteShare $noteShare)
    {
        $user = $request->user();

        // Owner can revoke, collaborator can leave
        if ($noteShare->owner_id !== $user->id && $noteShare->shared_with_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $noteShare->delete();

        return response()->json(['message' => 'Share removed.']);
    }

    /**
     * POST /api/note-shares/accept/{token}
     * Accept a share invitation via token.
     */
    public function accept(Request $request, string $token)
    {
        $share = NoteShare::where('invite_token', $token)->firstOrFail();

        if ($share->status !== 'pending') {
            return response()->json(['message' => 'This invitation has already been ' . $share->status . '.'], 422);
        }

        // Check expiry
        if ($share->expires_at && $share->expires_at->isPast()) {
            return response()->json(['message' => 'This invitation has expired.'], 410);
        }

        // If the user is authenticated, resolve and link them
        $user = $request->user();
        if ($user) {
            $share->update([
                'shared_with_id' => $user->id,
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);
        } else {
            // If not authenticated, just mark accepted and they'll be linked on login
            $share->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Invitation accepted.',
            'data' => $share->load(['note:id,title,color', 'owner:id,name,email']),
        ]);
    }

    /**
     * POST /api/note-shares/decline/{token}
     * Decline a share invitation.
     */
    public function decline(string $token)
    {
        $share = NoteShare::where('invite_token', $token)->firstOrFail();

        if ($share->status !== 'pending') {
            return response()->json(['message' => 'This invitation has already been ' . $share->status . '.'], 422);
        }

        $share->update(['status' => 'declined']);

        return response()->json(['message' => 'Invitation declined.']);
    }

    /**
     * GET /api/shared-notes/{noteId}
     * Fetch a shared note (permission-checked).
     */
    public function showSharedNote(Request $request, int $noteId)
    {
        $note = Note::findOrFail($noteId);
        $user = $request->user();

        // Owner can always view
        if ($note->user_id === $user->id) {
            return response()->json(['data' => $note->load('tags'), 'permission' => 'owner']);
        }

        // Check for accepted share
        $share = NoteShare::where('note_id', $note->id)
            ->where('shared_with_id', $user->id)
            ->accepted()
            ->notExpired()
            ->first();

        if (! $share) {
            return response()->json(['message' => 'You do not have access to this note.'], 403);
        }

        $note->load('tags');

        return response()->json([
            'data' => $note,
            'permission' => $share->permission,
            'share_id' => $share->id,
            'owner' => [
                'id' => $share->owner->id,
                'name' => $share->owner->name,
            ],
        ]);
    }

    /**
     * PUT /api/shared-notes/{noteId}
     * Edit a shared note (requires edit or admin permission).
     */
    public function updateSharedNote(Request $request, int $noteId)
    {
        $note = Note::findOrFail($noteId);
        $user = $request->user();

        // Owner can always edit
        if ($note->user_id === $user->id) {
            $validated = $request->validate([
                'title' => ['sometimes', 'string', 'max:255'],
                'content' => ['sometimes', 'string'],
                'content_format' => ['sometimes', 'in:html,markdown'],
            ]);

            $note->update($validated);

            return response()->json([
                'message' => 'Note updated.',
                'data' => $note->fresh()->load('tags'),
            ]);
        }

        // Check for edit/admin permission
        $share = NoteShare::where('note_id', $note->id)
            ->where('shared_with_id', $user->id)
            ->accepted()
            ->notExpired()
            ->whereIn('permission', ['edit', 'admin'])
            ->first();

        if (! $share) {
            return response()->json(['message' => 'You do not have edit access to this note.'], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'content_format' => ['sometimes', 'in:html,markdown'],
        ]);

        $note->update($validated);

        return response()->json([
            'message' => 'Note updated.',
            'data' => $note->fresh()->load('tags'),
        ]);
    }
}

