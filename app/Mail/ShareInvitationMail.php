<?php

namespace App\Mail;

use App\Models\NoteShare;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShareInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $type; // 'note' or 'canvas'

    public function __construct(
        public NoteShare|\App\Models\CanvasShare $share,
        string $type = 'note'
    ) {
        $this->type = $type;
    }

    public function envelope(): Envelope
    {
        $ownerName = $this->share->owner->name ?? 'Someone';
        $subject = $this->type === 'note'
            ? "{$ownerName} shared a note with you on AetherNote"
            : "{$ownerName} invited you to their canvas on AetherNote";

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.share-invitation',
            with: [
                'share' => $this->share,
                'type' => $this->type,
                'ownerName' => $this->share->owner->name ?? 'Someone',
                'permission' => $this->share->permission,
                'noteTitle' => $this->type === 'note'
                    ? ($this->share->note->title ?? 'Untitled')
                    : null,
                'acceptUrl' => rtrim(config('app.frontend_url'), '/') . '/invitations/accept/' . $this->share->invite_token . '?type=' . $this->type,
                'declineUrl' => rtrim(config('app.frontend_url'), '/') . '/invitations/decline/' . $this->share->invite_token . '?type=' . $this->type,
            ],
        );
    }
}
