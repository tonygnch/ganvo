<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\Money;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent when a merchant refunds an order (full or partial). Mirrors
 * the OrderPlaced / OrderShipped structure so the inbox layout stays
 * consistent.
 */
class OrderRefunded extends Notification
{
    /**
     * @param Order $order
     * @param int   $amountCents amount refunded on THIS event (not
     *                          cumulative — useful for partial refunds
     *                          where the customer might get several
     *                          emails over time).
     */
    public function __construct(public Order $order, public int $amountCents)
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

        $isFull = ($this->amountCents >= (int) $order->total_cents)
            || $order->status === Order::STATUS_REFUNDED;
        $amountFormatted = Money::format($this->amountCents, $order->currency);

        return (new MailMessage)
            ->subject(__('site.email.refunded_subject', [
                'number' => $order->order_number,
                'tenant' => $tenant->name,
            ]))
            ->greeting(__('site.email.refunded_greeting', ['name' => $order->customer_name ?: '']))
            ->line(__($isFull ? 'site.email.refunded_full' : 'site.email.refunded_partial', [
                'tenant' => $tenant->name,
                'amount' => $amountFormatted,
            ]))
            ->line(__('site.email.refunded_eta'))
            ->action(__('site.email.refunded_view_action'), $orderUrl);
    }
}
