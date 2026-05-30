<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\Shipping\CarrierRegistry;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderShipped extends Notification
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

        $carrierLabel = CarrierRegistry::label($order->carrier);

        // Fall back to the registry-computed URL when the stored one
        // is empty — covers legacy orders shipped before we started
        // auto-generating + any time the carrier exposes a public
        // tracking page.
        $trackingUrl = $order->tracking_url
            ?: CarrierRegistry::trackingUrlFor($order->carrier, $order->tracking_number);

        $mail = (new MailMessage)
            ->subject(__('site.email.shipped_subject', ['number' => $order->order_number, 'tenant' => $tenant->name]))
            ->greeting(__('site.email.shipped_greeting', ['name' => $order->customer_name ?: '']))
            ->line(__('site.email.shipped_body', ['tenant' => $tenant->name]))
            ->line(__('site.email.shipped_carrier', ['carrier' => $carrierLabel]))
            ->line(__('site.email.shipped_tracking', ['number' => $order->tracking_number]));

        if ($trackingUrl) {
            $mail->action(__('site.email.shipped_track_action'), $trackingUrl);
        }

        return $mail->action(__('site.email.shipped_view_action'), $orderUrl);
    }
}
