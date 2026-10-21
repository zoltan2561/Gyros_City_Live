<?php
namespace App\Services;

use App\Models\BarionSetting;
use App\Models\BarionTransaction;
use App\Models\PaymentLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use Barion\BarionClient;
use Barion\Enumerations\BarionEnvironment;
use Barion\Enumerations\Currency;
use Barion\Enumerations\PaymentType;
use Barion\Enumerations\FundingSourceType;
use Barion\Enumerations\UILocale;
use Barion\Models\Common\ItemModel;
use Barion\Models\Payment\PaymentTransactionModel;
use Barion\Models\Payment\PreparePaymentRequestModel;

class BarionService
{
    private BarionClient $bc;
    private BarionSetting $cfg;
    private string $env;

    /**
     * @param string|null $env 'test', 'prod', or null to use the application environment
     */
    public function __construct(?string $env = null)
    {
        // Use the same environment for payment creation, redirects and callbacks.
        $env ??= app()->environment('production', 'prod') ? 'prod' : 'test';
        $this->env = $env;
        $this->cfg = BarionSetting::where('env', $env)->where('is_enabled', 1)->firstOrFail();

        $this->bc = new BarionClient(
            poskey: $this->cfg->poskey,
            version: 2, // PreparePayment v2
            env: $env === 'prod' ? BarionEnvironment::Prod : BarionEnvironment::Test,
            useBundledRootCerts: false
        );
    }

    /**
     * Indít egy Barion fizetést, létrehozza a helyi tranzakciós sort és visszaadja a gateway URL-t.
     *
     * @param int    $orderId    Saját rendelés azonosító
     * @param string $payerEmail Vásárló e-mail (PayerHint)
     * @param array  $cart       Tételek: [['name','qty','price','sku'?], ...]
     * @param float  $total      Bruttó végösszeg
     * @return array {paymentId, gatewayUrl, status}
     */
    public function startPayment(int $orderId, string $payerEmail, array $cart, float $total): array
    {
        // 1) helyi sor
        $txRow = BarionTransaction::create([
            'order_id'    => $orderId,
            'status'      => 'Initialized',
            'amount'      => $total,
            'currency'    => $this->cfg->currency ?? 'HUF',
            'payer_email' => $payerEmail,
        ]);

        // 2) tranzakció + tételek
        $tx = new PaymentTransactionModel();
        $tx->POSTransactionId = 'TRANS-' . $orderId;
        $payee = trim((string)$this->cfg->shop_email);
        if ($payee === '') {
            throw new \RuntimeException('Barion: hiányzik a shop_email (Payee) a beállításból.');
        }
        $tx->Payee = $payee;

        $tx->Total            = $total;
        $tx->Comment          = 'Order #' . $orderId;

        foreach ($cart as $row) {
            $item = new ItemModel();
            $item->Name        = $row['name'];
            $item->Description = $row['name'];
            $item->Quantity    = (int) $row['qty'];
            $item->Unit        = 'db';
            $item->UnitPrice   = (float) $row['price'];
            $item->ItemTotal   = (float) $row['price'] * (int) $row['qty'];
            $item->SKU         = $row['sku'] ?? Str::slug($row['name']);
            $tx->AddItem($item);
        }

        // 3) request összeállítás
        $req = new PreparePaymentRequestModel();
        $req->GuestCheckout     = true;
        $req->PaymentType       = PaymentType::Immediate;
        $req->FundingSources    = [FundingSourceType::All];
        $req->PaymentRequestId  = 'PAY-' . $orderId;
        $req->PayerHint         = $payerEmail;
        $req->Locale            = UILocale::HU;
        $req->OrderNumber       = (string) $orderId;
        $req->Currency          = Currency::HUF;
        $req->RedirectUrl       = $this->cfg->redirect_url;
        $req->CallbackUrl       = $this->cfg->callback_url;
        $req->AddTransaction($tx);

        // 4) API hívás
        $resp = $this->bc->PreparePayment($req);
        $paymentId = $resp->PaymentId ?? null;

        PaymentLog::create([
            'payment_id' => $paymentId ?: 'N/A',
            'event'      => 'PREPARE',
            'message'    => $paymentId ? 'OK' : 'ERROR: no PaymentId'
        ]);

        if (!$paymentId) {
            throw new \RuntimeException('Barion PreparePayment hiba: nincs PaymentId.');
        }

        // 5) helyi sor frissítése
        $txRow->payment_id = $paymentId;
        $txRow->status     = $resp->Status ?? 'Initialized';
        $txRow->save();

        // 6) visszairányítási URL (a Barion fix Pay endpointja)
        $gatewayUrl = ($this->env === 'prod'
                ? 'https://secure.barion.com/Pay?id='
                : 'https://test.barion.com/Pay?id='
            ) . $paymentId;


        return [
            'paymentId'  => $paymentId,
            'gatewayUrl' => $gatewayUrl,
            'status'     => $txRow->status,
        ];
    }

    /**
     * PaymentState v4 lekérése.
     */
    public function fetchState(string $paymentId): object
    {
        $this->bc->SetVersion(4); // PaymentState v4
        return $this->bc->PaymentState($paymentId);
    }

    /**
     * Callback (IPN) feldolgozása idempotensen.
     */
    public function handleCallback(?string $paymentId): void
    {
        if (!$paymentId) {
            PaymentLog::create([
                'payment_id' => 'N/A',
                'event'      => 'CALLBACK',
                'message'    => 'Empty paymentId passed to handleCallback'
            ]);
            return;
        }

        DB::transaction(function () use ($paymentId) {
            $p = BarionTransaction::where('payment_id', $paymentId)->lockForUpdate()->first();

            if (!$p) {
                PaymentLog::create([
                    'payment_id' => $paymentId,
                    'event'      => 'CALLBACK',
                    'message'    => 'Unknown paymentId'
                ]);
                return;
            }

            $state = $this->fetchState($paymentId);

            $raw = $state->Status ?? '';
            if (is_object($raw)) {
                if (property_exists($raw, 'value')) {
                    $raw = $raw->value;
                } elseif (method_exists($raw, '__toString')) {
                    $raw = (string)$raw;
                } else {
                    $raw = get_class($raw);
                }
            }
            $new = $this->mapStatus((string)$raw) ?? (string)$raw;

            // Mindig frissítjük a raw_response + last_callback_at mezőket,
            // hogy lássuk, hogy a Barion elért minket.
            $p->raw_response     = json_encode($state, JSON_UNESCAPED_UNICODE);
            $p->last_callback_at = now();

            if ($new && $new !== $p->status) {
                $p->status = $new;
                $p->save();

                // Itt frissítsd a SAJÁT rendelést:
                // Order::where('id',$p->order_id)->update([...]);

                PaymentLog::create([
                    'payment_id' => $paymentId,
                    'event'      => 'CALLBACK',
                    'message'    => "Status -> {$new}"
                ]);
            } else {
                // nincs státuszváltozás, de mentsük el a mentett raw-t és az időt
                $p->save();

                PaymentLog::create([
                    'payment_id' => $paymentId,
                    'event'      => 'CALLBACK',
                    'message'    => "No change ({$p->status})"
                ]);
            }
        });
    }


    /**
     * Barion státusz -> belső státusz
     */
    private function mapStatus($barionStatus): ?string
    {
        // védő konverzió
        if (is_object($barionStatus)) {
            if (property_exists($barionStatus, 'value')) {
                $barionStatus = $barionStatus->value;
            } elseif (method_exists($barionStatus, '__toString')) {
                $barionStatus = (string)$barionStatus;
            } else {
                $barionStatus = get_class($barionStatus);
            }
        }
        $s = strtolower((string)$barionStatus);
        return match ($s) {
            'succeeded','completed' => 'Succeeded',
            'authorized'            => 'Authorized',
            'canceled','cancelled'  => 'Cancelled',
            'failed'                => 'Failed',
            'expired'               => 'Expired',
            'prepared'              => 'Prepared',
            default                 => $barionStatus ?: null,
        };
    }

}
