<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    /**
     * GET /api/tags
     * List all tags for the authenticated user, with note counts.
     */
    public function index(Request $request)
    {
        $tags = $request->user()->tags()
            ->withCount('notes')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $tags]);
    }

    /**
     * POST /api/tags
     * Create a new tag.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        // Prevent duplicate tag names per user
        $slug = Str::slug($validated['name']);
        $existing = $request->user()->tags()->where('slug', $slug)->first();

        if ($existing) {
            return response()->json([
                'message' => 'Tag already exists.',
                'data' => $existing,
            ], 409);
        }

        $tag = $request->user()->tags()->create($validated);

        return response()->json([
            'message' => 'Tag created.',
            'data' => $tag,
        ], 201);
    }

    /**
     * PUT /api/tags/{tag}
     * Update a tag's name or color.
     */
    public function update(Request $request, Tag $tag)
    {
        if ($tag->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:50'],
            'color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        // Re-generate slug if name changed
        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $tag->update($validated);

        return response()->json([
            'message' => 'Tag updated.',
            'data' => $tag->fresh(),
        ]);
    }

    /**
     * DELETE /api/tags/{tag}
     * Delete a tag. Pivot entries are cleaned up by cascadeOnDelete.
     */
    public function destroy(Request $request, Tag $tag)
    {
        if ($tag->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $tag->delete();

        return response()->json(['message' => 'Tag deleted.']);
    }
}
