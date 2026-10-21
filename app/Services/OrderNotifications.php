<?php

namespace App\Services;

use App\Mail\OrderNotificationMail;
use App\Models\CustomStatus;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class OrderNotifications
{
    public function placed(Order $order): bool
    {
        $sent = $this->send($order, 'Rendelésed megérkezett', 'Megkaptuk a rendelésed. Az elfogadásáról külön értesítést küldünk.');
        try {
            $admin = User::where('type', 1)->first();
            if ($admin && (string) $order->order_from !== 'pos') {
                \App\Helpers\helper::create_order_invoice($admin->email, $admin->name, $order->order_number,
                    $order, OrderDetails::where('order_id', $order->id)->get());
            }
        } catch (\Throwable $exception) {
            Log::warning('Restaurant order notification failed', ['order_id' => $order->id,
                'exception_class' => get_class($exception)]);
        }
        return $sent;
    }

    public function statusChanged(Order $order): bool
    {
        $status = CustomStatus::find($order->status);
        $name = (string) ($status?->name ?? 'Frissítve');
        $normalized = Str::lower(Str::ascii($name));
        if ((int) $order->status_type === 4) {
            return $this->send($order, 'Rendelés törölve', 'A rendelésedet töröltük. Ha kérdésed van, keresd az éttermet.');
        }
        if ((int) $order->status_type === 3) {
            return $this->send($order, 'Rendelés lezárva', 'A rendelésedet lezártuk. Köszönjük, hogy minket választottál!');
        }
        if (Str::contains($normalized, ['elkeszult', 'atveheto'])) {
            return (int) $order->order_type === 2
                ? $this->send($order, 'A rendelésed átvehető', 'A rendelésed elkészült, átveheted nálunk.')
                : $this->send($order, 'Az ételed elkészült', 'Az ételed elkészült.');
        }
        if (Str::contains($normalized, 'elfogad')) {
            return $this->send($order, 'Rendelésedet elfogadtuk', 'Elfogadtuk a rendelésed, és megkezdjük az elkészítését.');
        }
        return $this->send($order, 'Rendelésed állapota: '.$name, 'A rendelésed új állapota: '.$name.'.');
    }

    public function driverAssigned(Order $order): bool
    {
        return $this->send($order, 'Futárhoz került a rendelésed', 'A rendelésedet futárhoz rendeltük.');
    }

    private function send(Order $order, string $title, string $messageText): bool
    {
        if ((string) $order->order_from === 'pos') return false;
        try {
            $user = $order->user_id ? User::find($order->user_id) : null;
            if ($user && (int) $user->is_mail === 0) return false;
            // Use the contact supplied with this order; a guest does not need an account.
            $email = trim((string) ($order->email ?: $user?->email));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $email)) return false;
            $currency = (string) (Settings::first()?->currency ?: 'HUF');
            $items = OrderDetails::where('order_id', $order->id)->orderBy('id')->get();
            Mail::to($email)->send(new OrderNotificationMail($order, $items, $title, $messageText,
                (string) ($order->name ?: $user?->name), $currency));
            return true;
        } catch (\Throwable $exception) {
            // A mail outage must not turn a saved order into a failed checkout or duplicate order.
            Log::warning('Order notification failed', ['order_id' => $order->id,
                'status_id' => $order->status, 'exception_class' => get_class($exception)]);
            return false;
        }
    }
}
