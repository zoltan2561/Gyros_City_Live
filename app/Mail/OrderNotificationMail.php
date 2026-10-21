<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;

class OrderNotificationMail extends Mailable
{
    public $title;
    public $messageText;
    public $recipientName;
    public $summary;

    public function __construct(Order $order, Collection $items, string $title, string $messageText, string $recipientName, string $currency = 'HUF')
    {
        $this->title = $title;
        $this->messageText = $messageText;
        $this->recipientName = $recipientName;
        $money = fn ($amount) => number_format((float) $amount, $currency === 'HUF' ? 0 : 2, ',', ' ').' '.$currency;
        $schedule = trim((string) $order->delivery_date.' '.(string) $order->delivery_time);
        $this->summary = [
            'number' => $order->order_number,
            'name' => $order->name,
            'type' => (int) $order->order_type === 2 ? 'Elvitel' : 'Kiszállítás',
            'address' => (int) $order->order_type === 1 ? trim((string) $order->city.' '.(string) $order->address) : '',
            'schedule' => str_contains($schedule, '0000-00-00') ? '' : $schedule,
            'notes' => trim((string) $order->order_notes),
            'delivery' => (float) $order->delivery_charge > 0 ? $money($order->delivery_charge) : null,
            'discount' => (float) $order->discount_amount > 0 ? $money($order->discount_amount) : null,
            'total' => $money($order->grand_total),
            'items' => $items->map(function ($item) use ($money) {
                $unit = (float) $item->item_price + (float) $item->addons_total_price + (float) $item->extras_total_price;
                return ['name' => $item->item_name, 'qty' => (int) $item->qty,
                    'addons' => str_replace('| ', ' · ', (string) $item->addons_name),
                    'extras' => str_replace('| ', ' · ', (string) $item->extras_name),
                    'total' => $money($unit * (int) $item->qty)];
            })->all(),
        ];
    }

    public function build()
    {
        $subject = preg_replace('/[\r\n]+/', ' ', $this->title.' · '.$this->summary['number'].' | Gyros City');
        return $this->subject($subject)->view('email.order-notification')->text('email.order-notification-text');
    }
}
