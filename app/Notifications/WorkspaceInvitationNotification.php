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
        $inviter = $this->invitation->inviter->name ?? __('A teammate');

        return (new MailMessage)
            ->subject(__("You're invited to join :workspace on :app", [
                'workspace' => $workspace,
                'app' => config()->string('app.name'),
            ]))
            ->greeting(__('Hello!'))
            ->line(__(':inviter invited you to join **:workspace** as :role.', [
                'inviter' => $inviter,
                'workspace' => $workspace,
                'role' => $this->invitation->role->label(),
            ]))
            ->action(__('Accept invitation'), route('invitations.show', $this->token))
            ->line(__('This invitation expires in :days days.', ['days' => WorkspaceInvitation::EXPIRES_AFTER_DAYS]))
            ->line(__("If you weren't expecting this invitation, you can ignore this email."));
    }
}
