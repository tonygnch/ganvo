<?php

namespace App\Notifications;

use App\Models\StoreMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the merchant a visitor used their storefront contact form.
 *
 * The enquiry is already persisted when this is sent — if delivery fails the
 * merchant still finds it in Store admin → Messages, so this notification is
 * a convenience, never the system of record.
 */
class StoreMessageReceived extends Notification
{
    public function __construct(public StoreMessage $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Send in the platform's own language, not the visitor's. toMail() runs
     * inside the storefront request, where SetLocale has already applied the
     * visitor's cookie — without this a Bulgarian merchant would get their
     * inbox notification in whatever language the shopper happened to browse in.
     */
    public function locale(): string
    {
        return config('app.locale');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $m = $this->message;
        $tenant = $m->tenant;
        // The Store admin panel is bound to the CENTRAL domain; url() here would
        // resolve against the tenant storefront host and 404.
        $panelUrl = 'https://' . config('ganvo.central_domain') . '/store/messages';
        $subject = $m->subject
            ? __('site.storefront.contact.subject_' . $m->subject)
            : __('site.storefront.contact.subject_general');

        $mail = (new MailMessage)
            ->subject(__('site.email.message_subject', ['tenant' => $tenant->name, 'subject' => $subject]))
            ->greeting(__('site.email.message_greeting'))
            ->line(__('site.email.message_intro', ['tenant' => $tenant->name]))
            ->line(__('site.email.message_from', ['name' => $m->name, 'email' => $m->email]));

        if ($m->phone) {
            $mail->line(__('site.email.message_phone', ['phone' => $m->phone]));
        }

        $mail->line(__('site.email.message_topic', ['subject' => $subject]))
            ->line('---')
            ->line($m->message)
            ->line('---')
            // Reply-to the visitor so the merchant can just hit reply.
            ->replyTo($m->email, $m->name)
            ->action(__('site.email.message_action'), $panelUrl)
            ->line(__('site.email.message_outro'));

        return $mail;
    }
}
