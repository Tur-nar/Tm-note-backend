<?php

namespace App\Services;

use App\Events\NewNotification;
use App\Models\Notification;

/**
 * Centralized notification creation with optional debouncing.
 *
 * Debounce logic: For "note_updated" notifications, we check if an identical
 * notification was created within the last 5 minutes. If so, we skip creating
 * a duplicate. This prevents flooding the user with "X edited your note"
 * notifications during active collaboration sessions.
 */
class NotificationService
{
    /**
     * Create a notification and broadcast it in real-time.
     *
     * @param int    $userId  Recipient user ID
     * @param string $type    Notification type (share_invitation, share_accepted, note_updated)
     * @param string $title   Short notification title
     * @param string $message Detailed message
     * @param array  $data    Extra payload (note_id, share_id, etc.)
     * @param bool   $debounce Whether to apply 5-minute debounce (default: false)
     */
    public static function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $debounce = false
    ): ?Notification {
        // Debounce: skip if same type + same data payload within 5 minutes
        if ($debounce) {
            $recent = Notification::where('user_id', $userId)
                ->where('type', $type)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->latest()
                ->first();

            if ($recent) {
                // Check if the note_id matches (for note_updated debouncing)
                $recentNoteId = $recent->data['note_id'] ?? null;
                $newNoteId = $data['note_id'] ?? null;
                $recentUpdatedBy = $recent->data['updated_by'] ?? null;
                $newUpdatedBy = $data['updated_by'] ?? null;

                if ($recentNoteId === $newNoteId && $recentUpdatedBy === $newUpdatedBy) {
                    return null; // Skip — duplicate within debounce window
                }
            }
        }

        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        // Broadcast to user's private channel for real-time delivery
        broadcast(new NewNotification($notification));

        return $notification;
    }
}
