<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ArchiveOrdersTest extends TestCase
{
    private string $archive;

    protected function setUp(): void
    {
        parent::setUp();
        $this->archive = sys_get_temp_dir().DIRECTORY_SEPARATOR.'gc-archive-'.bin2hex(random_bytes(8)).'.jsonl.gz';
        config(['database.default' => 'archive_test', 'database.connections.archive_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('order', function (Blueprint $t) {
            $t->id(); $t->integer('status_type'); $t->integer('payment_status')->default(1);
            $t->integer('transaction_type')->default(1); $t->timestamp('created_at');
        });
        Schema::create('order_details', function (Blueprint $t) {
            $t->id(); $t->integer('order_id'); $t->string('item_name');
        });
        foreach ([[1, 3, 1, 1, '2026-09-21'], [2, 4, 16, 2, '2026-09-21'],
            [3, 2, 1, 1, '2026-09-21'], [4, 1, 1, 1, '2026-09-21'],
            [5, 3, 16, 1, '2026-09-21'], [6, 3, 1, 1, '2026-09-22']] as [$id, $status, $type, $paid, $date]) {
            DB::table('order')->insert(['id' => $id, 'status_type' => $status, 'transaction_type' => $type, 'payment_status' => $paid, 'created_at' => $date.' 00:00:00']);
            DB::table('order_details')->insert(['order_id' => $id, 'item_name' => 'Pizza '.$id]);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->archive)) unlink($this->archive);
        parent::tearDown();
    }

    public function test_dry_run_does_not_write_files_or_change_orders(): void
    {
        $this->artisan('orders:archive', ['--before' => '2026-09-22', '--output' => $this->archive])
            ->expectsOutput('Eligible closed orders before 2026-09-22: 2')->assertSuccessful();
        $this->assertFileDoesNotExist($this->archive);
        $this->assertSame(6, DB::table('order')->count());
    }

    public function test_archive_contains_closed_orders_and_details_and_keeps_searchable_source(): void
    {
        $this->artisan('orders:archive', ['--before' => '2026-09-22', '--output' => $this->archive, '--write' => true])->assertSuccessful();
        $records = array_map(fn ($line) => json_decode($line, true, 512, JSON_THROW_ON_ERROR), explode("\n", trim(gzdecode(file_get_contents($this->archive)))));
        $this->assertSame('gyros-city-orders-v1', $records[0]['format']);
        $this->assertSame([1, 2], array_column(array_column(array_slice($records, 1, 2), 'order'), 'id'));
        $this->assertSame('Pizza 1', $records[1]['order_details'][0]['item_name']);
        $this->assertSame(['complete' => true, 'orders' => 2], $records[3]);
        $this->assertSame(6, DB::table('order')->count());
        $this->assertSame(6, DB::table('order_details')->count());
        $original = hash_file('sha256', $this->archive);
        $this->artisan('orders:archive', ['--before' => '2026-09-22', '--output' => $this->archive, '--write' => true])->assertFailed();
        $this->assertSame($original, hash_file('sha256', $this->archive));
    }

    public function test_invalid_date_and_public_output_are_rejected(): void
    {
        $this->artisan('orders:archive', ['--before' => '2026-02-30'])->assertFailed();
        $this->artisan('orders:archive', ['--before' => '2026-09-22', '--output' => public_path('archive.jsonl.gz'), '--write' => true])->assertFailed();
        $this->assertFileDoesNotExist(public_path('archive.jsonl.gz'));
    }
}
