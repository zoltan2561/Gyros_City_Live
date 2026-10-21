<?php

namespace Tests\Feature;

use App\Services\BarionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BarionEnvironmentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'barion_environment_test', 'database.connections.barion_environment_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('barion_settings', function (Blueprint $table) {
            $table->id();
            $table->string('env');
            $table->boolean('is_enabled');
            $table->string('poskey');
        });
    }

    /** @dataProvider environments */
    public function test_start_and_callback_service_use_the_configuration_for_the_application_environment(string $appEnvironment, string $expected): void
    {
        $this->app->instance('env', $appEnvironment);
        $this->setting($expected);
        // Constructing the service does not call Barion or start a payment.
        $this->assertInstanceOf(BarionService::class, app(BarionService::class));
    }

    public static function environments(): array
    {
        return [['production', 'prod'], ['prod', 'prod'], ['staging', 'test'], ['local', 'test'], ['testing', 'test']];
    }

    public function test_an_explicit_environment_remains_supported(): void
    {
        $this->app->instance('env', 'production');
        $this->setting('test');
        $this->assertInstanceOf(BarionService::class, app(BarionService::class, ['env' => 'test']));
    }

    private function setting(string $environment): void
    {
        DB::table('barion_settings')->insert([
            'env' => $environment, 'is_enabled' => 1,
            'poskey' => '00000000-0000-0000-0000-000000000000',
        ]);
    }
}
