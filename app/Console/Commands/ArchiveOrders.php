<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveOrders extends Command
{
    protected $signature = 'orders:archive {--before= : Exclusive YYYY-MM-DD cutoff; defaults to one calendar month ago}
        {--output= : Absolute .jsonl.gz path outside the project and webroot}
        {--write : Write the archive; otherwise only report counts}';

    protected $description = 'Export older closed orders and their lines without removing searchable history.';

    public function handle(): int
    {
        $value = $this->option('before') ?: CarbonImmutable::now('Europe/Budapest')->subMonthNoOverflow()->format('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) || !checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4))) {
            $this->error('Invalid cutoff date. Use YYYY-MM-DD.');
            return self::FAILURE;
        }
        $cutoff = $value.' 00:00:00';
        $query = DB::table('order')->where('created_at', '<', $cutoff)->whereIn('status_type', [3, 4])
            ->where(fn ($q) => $q->where('transaction_type', 1)->orWhere('payment_status', 2));
        $this->info('Eligible closed orders before '.$value.': '.(clone $query)->count());
        if (!$this->option('write')) return self::SUCCESS;

        $path = (string) $this->option('output');
        $parent = realpath(dirname($path));
        $project = realpath(base_path());
        $normalize = fn ($p) => strtolower(str_replace('\\', '/', $p));
        if (!$parent || !preg_match('/\.jsonl\.gz$/D', $path) || $path !== $parent.DIRECTORY_SEPARATOR.basename($path)
            || $normalize($parent) === $normalize($project) || str_starts_with($normalize($parent).'/', $normalize($project).'/')
            || file_exists($path) || is_link($path)) {
            $this->error('Use a new absolute .jsonl.gz filename in an existing private directory outside the project.');
            return self::FAILURE;
        }
        // Exclusive creation prevents overwriting an earlier archive.
        $file = fopen($path, 'xb');
        if (!$file) return self::FAILURE;
        chmod($path, 0600);
        $count = 0;
        try {
            $compressor = deflate_init(ZLIB_ENCODING_GZIP);
            DB::transaction(function () use ($query, $cutoff, $file, $compressor, &$count) {
                $write = function (array $record) use ($file, $compressor) {
                    $compressed = deflate_add($compressor, json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)."\n", ZLIB_NO_FLUSH);
                    if ($compressed === false || fwrite($file, $compressed) !== strlen($compressed)) {
                        throw new \RuntimeException('Archive write failed.');
                    }
                };
                $write(['format' => 'gyros-city-orders-v1', 'before' => $cutoff, 'deletes_source' => false]);
                (clone $query)->orderBy('id')->chunkById(100, function ($orders) use ($write, &$count) {
                    $lines = DB::table('order_details')->whereIn('order_id', $orders->pluck('id'))->orderBy('id')->get()->groupBy('order_id');
                    foreach ($orders as $order) {
                        $write(['order' => $order, 'order_details' => ($lines->get($order->id) ?? collect())->values()->all()]);
                        $count++;
                    }
                });
                $write(['complete' => true, 'orders' => $count]);
            });
            $end = deflate_add($compressor, '', ZLIB_FINISH);
            if ($end === false || fwrite($file, $end) !== strlen($end)) throw new \RuntimeException('Archive finalization failed.');
        } catch (\Throwable $e) {
            fclose($file);
            $this->error('Archive incomplete; source rows unchanged. '.get_class($e));
            return self::FAILURE;
        }
        fclose($file);
        $this->info('Archived '.$count.' orders; source rows unchanged. SHA256: '.hash_file('sha256', $path));
        return self::SUCCESS;
    }
}
