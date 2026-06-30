<?php

namespace App\Http\Controllers;

use App\Mail\ShareInvitationMail;
use App\Models\CanvasShare;
use App\Models\Note;
use App\Models\NoteShare;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CanvasShareController extends Controller
{
    /**
     * GET /api/canvas-shares
     * List canvas shares sent BY the authenticated user.
     */
    public function index(Request $request)
    {
        $shares = $request->user()
            ->sentCanvasShares()
            ->with('sharedWith:id,name,email')
            ->latest()
            ->get();

        return response()->json(['data' => $shares]);
    }

    /**
     * GET /api/canvas-shares/received
     * List canvas shares received BY the authenticated user.
     */
    public function received(Request $request)
    {
        $shares = CanvasShare::where('shared_with_id', $request->user()->id)
            ->with('owner:id,name,email')
            ->latest()
            ->get();

        return response()->json(['data' => $shares]);
    }

    /**
     * POST /api/canvas-shares
     * Share canvas workspace with an email.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'permission' => ['required', 'in:view,edit'],
        ]);

        $user = $request->user();

        // Can't share with yourself
        if (strtolower($validated['email']) === strtolower($user->email)) {
            return response()->json(['message' => 'You cannot share your canvas with yourself.'], 422);
        }

        // Check for duplicate
        $existing = CanvasShare::where('owner_id', $user->id)
            ->where('shared_email', strtolower($validated['email']))
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Your canvas is already shared with that email.'], 409);
        }

        $targetUser = User::where('email', strtolower($validated['email']))->first();

        $share = CanvasShare::create([
            'owner_id' => $user->id,
            'shared_with_id' => $targetUser?->id,
            'shared_email' => strtolower($validated['email']),
            'permission' => $validated['permission'],
            'status' => 'pending',
            'invite_token' => Str::random(64),
        ]);

        // Send invitation email
        Mail::to($validated['email'])->send(new ShareInvitationMail($share, 'canvas'));

        $share->load('sharedWith:id,name,email');

        return response()->json([
            'message' => 'Canvas shared successfully. Invitation sent.',
            'data' => $share,
        ], 201);
    }

    /**
     * DELETE /api/canvas-shares/{canvasShare}
     * Revoke a canvas share.
     */
    public function destroy(Request $request, CanvasShare $canvasShare)
    {
        $user = $request->user();

        if ($canvasShare->owner_id !== $user->id && $canvasShare->shared_with_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $canvasShare->delete();

        return response()->json(['message' => 'Canvas share removed.']);
    }

    /**
     * POST /api/canvas-shares/accept/{token}
     * Accept a canvas share invitation.
     */
    public function accept(Request $request, string $token)
    {
        $share = CanvasShare::where('invite_token', $token)->firstOrFail();

        if ($share->status !== 'pending') {
            return response()->json(['message' => 'This invitation has already been ' . $share->status . '.'], 422);
        }

        $user = $request->user();
        $share->update([
            'shared_with_id' => $user?->id ?? $share->shared_with_id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return response()->json([
            'message' => 'Canvas invitation accepted.',
            'data' => $share->load('owner:id,name,email'),
        ]);
    }

    /**
     * POST /api/canvas-shares/decline/{token}
     * Decline a canvas share invitation.
     */
    public function decline(string $token)
    {
        $share = CanvasShare::where('invite_token', $token)->firstOrFail();

        if ($share->status !== 'pending') {
            return response()->json(['message' => 'This invitation has already been ' . $share->status . '.'], 422);
        }

        $share->update(['status' => 'declined']);

        return response()->json(['message' => 'Canvas invitation declined.']);
    }

    /**
     * GET /api/shared-canvas/{ownerId}
     * View a shared canvas (permission-checked).
     * Returns the owner's notes that are also shared with the viewer.
     */
    public function sharedCanvas(Request $request, int $ownerId)
    {
        $user = $request->user();

        // Check canvas share exists and is accepted
        $canvasShare = CanvasShare::where('owner_id', $ownerId)
            ->where('shared_with_id', $user->id)
            ->accepted()
            ->first();

        if (! $canvasShare) {
            return response()->json(['message' => 'You do not have access to this canvas.'], 403);
        }

        // Get notes shared with this user from this owner
        $sharedNoteIds = NoteShare::where('owner_id', $ownerId)
            ->where('shared_with_id', $user->id)
            ->accepted()
            ->pluck('note_id');

        $notes = Note::whereIn('id', $sharedNoteIds)
            ->with('tags')
            ->get();

        return response()->json([
            'data' => $notes,
            'permission' => $canvasShare->permission,
            'owner' => [
                'id' => $canvasShare->owner->id,
                'name' => $canvasShare->owner->name,
            ],
        ]);
    }
}
