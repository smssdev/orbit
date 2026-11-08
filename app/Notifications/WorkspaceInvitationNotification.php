<?php

namespace App\Notifications;

use App\Models\WorkspaceInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The plain-text token is only ever held here, on its way into the email; the database stores its hash.
     */
    public function __construct(
        public readonly WorkspaceInvitation $invitation,
        public readonly string $token,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workspace = $this->invitation->workspace->name;
        $inviter = $this->invitation->inviter->name ?? 'A teammate';

        return (new MailMessage)
            ->subject("You're invited to join {$workspace} on ".config()->string('app.name'))
            ->greeting('Hello!')
            ->line("{$inviter} invited you to join **{$workspace}** as {$this->invitation->role->label()}.")
            ->action('Accept invitation', route('invitations.show', $this->token))
            ->line('This invitation expires in '.WorkspaceInvitation::EXPIRES_AFTER_DAYS.' days.')
            ->line("If you weren't expecting this invitation, you can ignore this email.");
    }
}
