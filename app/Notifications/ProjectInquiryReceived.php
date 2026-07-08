<?php

namespace App\Notifications;

use App\Models\ProjectInquiry;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the studio owner when a visitor submits the "Start a project" form
 * on the marketing homepage. Owner-facing (not customer-facing) — a heads-up
 * with the lead's details and a pointer to the admin inbox. The lead itself
 * is persisted independently, so this is best-effort.
 */
class ProjectInquiryReceived extends Notification
{
    public function __construct(public ProjectInquiry $inquiry)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $i = $this->inquiry;
        $adminUrl = 'http://' . config('ganvo.central_domain') . '/super';

        $mail = (new MailMessage)
            ->subject('New project inquiry — ' . $i->name)
            ->greeting('New project inquiry')
            ->line($i->name . ' <' . $i->email . '> wants to start a project.');

        if ($i->company) {
            $mail->line('Company: ' . $i->company);
        }
        if ($i->project_type) {
            $mail->line('Project type: ' . $i->project_type);
        }
        if ($i->budget) {
            $mail->line('Budget: ' . $i->budget);
        }

        return $mail
            ->line('Message:')
            ->line($i->message)
            ->action('Open the inquiries inbox', $adminUrl)
            ->line('Reply directly to ' . $i->email . '.');
    }
}
