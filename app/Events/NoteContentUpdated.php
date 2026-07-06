<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Lightweight broadcast signal when a note's content is updated.
 *
 * IMPORTANT: Does NOT include the full note content in the payload.
 * Reverb/Pusher has a ~10KB message size limit, and note content can
 * easily exceed that. Instead, we send only a small signal with metadata.
 * The frontend receives this signal and fetches the latest content via API.
 *
 * Channel: presence-note.{noteId}
 * Event name: NoteContentUpdated
 *
 * Uses ShouldBroadcastNow to bypass the queue and broadcast immediately.
 */
class NoteContentUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $noteId;
    public int $updatedBy;
    public string $updatedByName;
    public int $timestamp;

    public function __construct(int $noteId, int $updatedBy, string $updatedByName)
    {
        $this->noteId = $noteId;
        $this->updatedBy = $updatedBy;
        $this->updatedByName = $updatedByName;
        $this->timestamp = time();
    }

    /**
     * Broadcast on the note's presence channel.
     * Only users who have joined (owner + accepted collaborators) receive this.
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("note.{$this->noteId}"),
        ];
    }

    /**
     * Lightweight payload — no content, just a "something changed" signal.
     * The frontend will re-fetch the note via GET /api/notes/{id} or
     * GET /api/shared-notes/{id} upon receiving this.
     */
    public function broadcastWith(): array
    {
        return [
            'noteId' => $this->noteId,
            'updated_by' => $this->updatedBy,
            'updated_by_name' => $this->updatedByName,
            'timestamp' => $this->timestamp,
        ];
    }
}
