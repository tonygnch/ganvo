<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlaced extends Notification
{
    public function __construct(public Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $tenant = $order->tenant;
        $storeUrl = 'http://' . $tenant->slug . '.' . config('ganvo.central_domain');
        $orderUrl = $storeUrl . '/orders/' . $order->order_number;

        $mail = (new MailMessage)
            ->subject(__('site.email.placed_subject', ['number' => $order->order_number, 'tenant' => $tenant->name]))
            ->greeting(__('site.email.placed_greeting', ['name' => $order->customer_name ?: '']))
            ->line(__('site.email.placed_thanks', ['tenant' => $tenant->name]))
            ->line(__('site.email.placed_line_order', ['number' => $order->order_number]))
            ->line(__('site.email.placed_line_total', ['amount' => number_format($order->total_cents / 100, 2), 'currency' => $order->currency]));

        foreach ($order->items as $item) {
            $mail->line(__('site.email.placed_line_item', [
                'name' => $item->displayName(),
                'qty' => $item->quantity,
                'amount' => number_format($item->subtotal_cents / 100, 2),
            ]));
        }

        return $mail
            ->action(__('site.email.placed_action'), $orderUrl)
            ->line(__('site.email.placed_outro'));
    }
}
