<?php
// public_html/cron/decide_unprocessed.php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';

/** @var \Illuminate\Contracts\Console\Kernel $consoleKernel */
$consoleKernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);

// Laravel környezet felhúzása, hogy a Facade-ok menjenek
$consoleKernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// ===== KONFIG =====
$minutes = 10; // teszthez 2-re állíthatod
$tz      = 'Europe/Budapest';

// API endpoint (ajánlott az api.php alatt CSRF nélkül)
$apiUrl  = 'https://gyroscity.eu/admin/order-unprocessed-alert';

// opcionális shared token (ajánlott)
$token   = getenv('UNPROC_ALERT_TOKEN') ?: ''; // vagy írd be fixen

// ===== LEKÉRDEZÉS =====
$cutoff = Carbon::now($tz)->subMinutes($minutes);

$rows = DB::table('order') // ha a tábla neve "orders", írd át!
    ->select('id')
    ->where('status_type', 1)
    ->where('created_at', '<=', $cutoff)
    ->orderBy('created_at', 'asc')
    ->limit(500)
    ->get();

$nowStr = date('Y-m-d H:i:s T');

if ($rows->isEmpty()) {
    echo "$nowStr | no type1 older than {$minutes} min -> no call\n";
    exit(0);
}

// ID-k listája
$ids = $rows->pluck('id')->all();
echo "$nowStr | found ".count($ids)." ids -> calling API\n";

// ===== API HÍVÁS (POST JSON) =====
$payload = [
    'ids'     => $ids,
    'minutes' => $minutes,
];
if ($token !== '') {
    $payload['token'] = $token;
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $apiUrl,
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 20,
]);
$resp = curl_exec($ch);
$err  = curl_error($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($err) {
    echo "$nowStr | cURL ERROR: $err\n";
    exit(1);
}
echo "$nowStr | API HTTP $code | resp: ".trim((string)$resp)."\n";
