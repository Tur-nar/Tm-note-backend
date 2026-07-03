<?php

namespace App\Events;

use App\Models\Note;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast when a note's content is updated.
 *
 * This event is dispatched after a note is saved (by owner or collaborator).
 * All users in the note's presence channel receive the update, allowing
 * their editors to reflect changes in real-time without refreshing.
 *
 * Channel: presence-note.{noteId}
 * Event name: NoteContentUpdated
 */
class NoteContentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $noteId;
    public string $title;
    public string $content;
    public string $contentFormat;
    public int $updatedBy;
    public string $updatedByName;

    public function __construct(Note $note, int $updatedBy, string $updatedByName)
    {
        $this->noteId = $note->id;
        $this->title = $note->title;
        $this->content = $note->content ?? '';
        $this->contentFormat = $note->content_format;
        $this->updatedBy = $updatedBy;
        $this->updatedByName = $updatedByName;
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
     * Data payload sent to connected clients.
     */
    public function broadcastWith(): array
    {
        return [
            'noteId' => $this->noteId,
            'title' => $this->title,
            'content' => $this->content,
            'content_format' => $this->contentFormat,
            'updated_by' => $this->updatedBy,
            'updated_by_name' => $this->updatedByName,
        ];
    }
}
