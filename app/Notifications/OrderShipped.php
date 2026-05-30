<?php

namespace App\Notifications;

use App\Models\Order;
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

        // Carrier slug → display label. Mirror the StoreAdmin
        // ViewOrder list so admin + email stay in sync.
        $carrierLabel = match ($order->carrier) {
            'dpd'    => 'DPD',
            'gls'    => 'GLS',
            'dhl'    => 'DHL',
            'postnl' => 'PostNL',
            'econt'  => 'Econt',
            'speedy' => 'Speedy',
            'ups'    => 'UPS',
            'usps'   => 'USPS',
            'fedex'  => 'FedEx',
            default  => ucfirst((string) $order->carrier),
        };

        $mail = (new MailMessage)
            ->subject(__('site.email.shipped_subject', ['number' => $order->order_number, 'tenant' => $tenant->name]))
            ->greeting(__('site.email.shipped_greeting', ['name' => $order->customer_name ?: '']))
            ->line(__('site.email.shipped_body', ['tenant' => $tenant->name]))
            ->line(__('site.email.shipped_carrier', ['carrier' => $carrierLabel]))
            ->line(__('site.email.shipped_tracking', ['number' => $order->tracking_number]));

        if ($order->tracking_url) {
            $mail->action(__('site.email.shipped_track_action'), $order->tracking_url);
        }

        return $mail->action(__('site.email.shipped_view_action'), $orderUrl);
    }
}
