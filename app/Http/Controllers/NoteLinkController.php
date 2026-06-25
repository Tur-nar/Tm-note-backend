<?php

namespace App\Http\Controllers;

use App\Models\NoteLink;
use Illuminate\Http\Request;

class NoteLinkController extends Controller
{
    /**
     * GET /api/note-links
     * List all links for the authenticated user.
     */
    public function index(Request $request)
    {
        $links = $request->user()
            ->noteLinks()
            ->with(['sourceNote:id,title,x_position,y_position,color', 'targetNote:id,title,x_position,y_position,color'])
            ->get();

        return response()->json([
            'data' => $links,
        ]);
    }

    /**
     * POST /api/note-links
     * Create a directional link between two notes.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_note_id' => ['required', 'integer', 'exists:notes,id'],
            'target_note_id' => ['required', 'integer', 'exists:notes,id', 'different:source_note_id'],
        ]);

        $userId = $request->user()->id;

        // Verify the user owns both notes
        $userNoteIds = $request->user()->notes()->pluck('id');
        if (!$userNoteIds->contains($validated['source_note_id']) || !$userNoteIds->contains($validated['target_note_id'])) {
            return response()->json(['message' => 'You can only link your own notes.'], 403);
        }

        // Check for duplicate (in either direction)
        $exists = NoteLink::where(function ($q) use ($validated) {
            $q->where('source_note_id', $validated['source_note_id'])
              ->where('target_note_id', $validated['target_note_id']);
        })->orWhere(function ($q) use ($validated) {
            $q->where('source_note_id', $validated['target_note_id'])
              ->where('target_note_id', $validated['source_note_id']);
        })->exists();

        if ($exists) {
            return response()->json(['message' => 'These notes are already linked.'], 409);
        }

        $link = NoteLink::create([
            'source_note_id' => $validated['source_note_id'],
            'target_note_id' => $validated['target_note_id'],
            'user_id'        => $userId,
        ]);

        $link->load(['sourceNote:id,title,x_position,y_position,color', 'targetNote:id,title,x_position,y_position,color']);

        return response()->json([
            'message' => 'Notes linked successfully.',
            'data'    => $link,
        ], 201);
    }

    /**
     * DELETE /api/note-links/{noteLink}
     * Delete a link.
     */
    public function destroy(Request $request, NoteLink $noteLink)
    {
        if ($noteLink->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Link not found.'], 404);
        }

        $noteLink->delete();

        return response()->json([
            'message' => 'Link removed.',
        ]);
    }
}
