<?php
namespace Tests\Unit;
use Tests\TestCase;
class SignageModuleSwitchTest extends TestCase {
    private function enabled(): bool {
        return (bool) (require config_path('signage.php'))['enabled'];
    }
    public function test_the_module_is_off_unless_the_environment_switches_it_on(): void {
        putenv('SIGNAGE_ENABLED');
        unset($_ENV['SIGNAGE_ENABLED'], $_SERVER['SIGNAGE_ENABLED']);
        $this->assertFalse($this->enabled());
        foreach (['true', '1'] as $value) { putenv('SIGNAGE_ENABLED=' . $value); $this->assertTrue($this->enabled(), $value); }
        foreach (['false', '0', ''] as $value) { putenv('SIGNAGE_ENABLED=' . $value); $this->assertFalse($this->enabled(), $value); }
        putenv('SIGNAGE_ENABLED');
    }
}
