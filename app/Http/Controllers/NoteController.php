<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;
use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;

class NoteController extends Controller
{
    /**
     * GET /api/notes
     * List authenticated user's notes (active only, paginated).
     * Supports: ?search=keyword&sortBy=title&sortDir=asc&archived=1
     */
    public function index(Request $request)
    {
        $query = $request->user()->notes();

        // Show archived or active
        if ($request->boolean('archived')) {
            $query->archived();
        } else {
            $query->active();
        }

        // Apply filters (search + sort)
        $query->filter($request->only(['search', 'sortBy', 'sortDir']));

        // Pinned notes first
        $query->orderByDesc('is_pinned');

        $notes = $query->paginate($request->integer('per_page', 15));

        return response()->json($notes);
    }

    /**
     * POST /api/notes
     * Create a new note for the authenticated user.
     */
    public function store(StoreNoteRequest $request)
    {
        $data = $request->validated();
        $data['content'] = $data['content'] ?? '';

        $note = $request->user()->notes()->create($data);

        return response()->json([
            'message' => 'Note created successfully.',
            'data'    => $note,
        ], 201);
    }

    /**
     * GET /api/notes/{note}
     * Show a single note (only if the user owns it).
     */
    public function show(Request $request, Note $note)
    {
        // Ownership check
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Note not found.'], 404);
        }

        return response()->json([
            'data' => $note,
        ]);
    }

    /**
     * PUT /api/notes/{note}
     * Update a note. Used by auto-save (debounced from editor).
     */
    public function update(UpdateNoteRequest $request, Note $note)
    {
        $note->update($request->validated());

        return response()->json([
            'message' => 'Note updated successfully.',
            'data'    => $note->fresh(),
        ]);
    }

    /**
     * DELETE /api/notes/{note}
     * Permanently delete a note.
     */
    public function destroy(Request $request, Note $note)
    {
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Note not found.'], 404);
        }

        $note->delete();

        return response()->json([
            'message' => 'Note deleted successfully.',
        ]);
    }

    /**
     * PATCH /api/notes/{note}/archive
     * Soft-archive or unarchive a note.
     */
    public function toggleArchive(Request $request, Note $note)
    {
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Note not found.'], 404);
        }

        $note->archived_at = $note->archived_at ? null : now();
        $note->save();

        return response()->json([
            'message' => $note->archived_at ? 'Note archived.' : 'Note unarchived.',
            'data'    => $note,
        ]);
    }

    /**
     * PATCH /api/notes/{note}/pin
     * Toggle pinned state.
     */
    public function togglePin(Request $request, Note $note)
    {
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Note not found.'], 404);
        }

        $note->is_pinned = !$note->is_pinned;
        $note->save();

        return response()->json([
            'message' => $note->is_pinned ? 'Note pinned.' : 'Note unpinned.',
            'data'    => $note,
        ]);
    }
}
