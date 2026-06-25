<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Http\Requests\UpdateNoteRequest;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    /**
     * GET /api/notes
     * List authenticated user's notes (active, archived, or trashed).
     * Supports: ?search=keyword&sortBy=title&sortDir=asc&archived=1&trashed=1&tag=slug
     */
    public function index(Request $request)
    {
        $query = $request->user()->notes()->with('tags');

        // Show trashed, archived, or active
        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        } elseif ($request->boolean('archived')) {
            $query->archived();
        } else {
            $query->active();
        }

        // Filter by tag slug
        if ($tag = $request->get('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $tag));
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

        // Attach tags if provided (max 5, validated in FormRequest)
        if ($request->has('tag_ids')) {
            $userTagIds = $request->user()->tags()->pluck('id');
            $validIds = collect($request->input('tag_ids'))->intersect($userTagIds);
            $note->tags()->sync($validIds);
        }

        return response()->json([
            'message' => 'Note created successfully.',
            'data' => $note->load('tags'),
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

        $note->load('tags');

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

        // Sync tags if provided
        if ($request->has('tag_ids')) {
            $userTagIds = $request->user()->tags()->pluck('id');
            $validIds = collect($request->input('tag_ids'))->intersect($userTagIds);
            $note->tags()->sync($validIds);
        }

        return response()->json([
            'message' => 'Note updated successfully.',
            'data' => $note->fresh()->load('tags'),
        ]);
    }

    /**
     * DELETE /api/notes/{note}
     * Soft-delete a note (moves to trash).
     */
    public function destroy(Request $request, Note $note)
    {
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Note not found.'], 404);
        }

        $note->delete(); // SoftDeletes: sets deleted_at instead of removing

        return response()->json([
            'message' => 'Note moved to trash.',
        ]);
    }

    /**
     * PATCH /api/notes/{id}/restore
     * Restore a soft-deleted note from the trash.
     */
    public function restore(Request $request, int $id)
    {
        $note = $request->user()->notes()->onlyTrashed()->findOrFail($id);
        $note->restore();

        return response()->json([
            'message' => 'Note restored.',
            'data' => $note->load('tags'),
        ]);
    }

    /**
     * DELETE /api/notes/{id}/force
     * Permanently delete a trashed note.
     */
    public function forceDelete(Request $request, int $id)
    {
        $note = $request->user()->notes()->onlyTrashed()->findOrFail($id);
        $note->forceDelete();

        return response()->json([
            'message' => 'Note permanently deleted.',
        ]);
    }

    /**
     * DELETE /api/notes/trash/empty
     * Permanently delete all trashed notes.
     */
    public function emptyTrash(Request $request)
    {
        $request->user()->notes()->onlyTrashed()->forceDelete();

        return response()->json([
            'message' => 'Trash emptied.',
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
            'data' => $note,
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

        $note->is_pinned = ! $note->is_pinned;
        $note->save();

        return response()->json([
            'message' => $note->is_pinned ? 'Note pinned.' : 'Note unpinned.',
            'data' => $note,
        ]);
    }

    /**
     * PATCH /api/notes/{note}/position
     * Update a note's canvas position. Lightweight endpoint for drag-end saves.
     */
    public function updatePosition(Request $request, Note $note)
    {
        if ($note->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Note not found.'], 404);
        }

        $validated = $request->validate([
            'x_position' => ['required', 'numeric'],
            'y_position' => ['required', 'numeric'],
        ]);

        $note->update($validated);

        return response()->json([
            'message' => 'Position updated.',
            'data' => $note,
        ]);
    }
}
