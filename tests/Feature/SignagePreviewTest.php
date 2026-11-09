<?php

namespace Tests\Feature;

class SignagePreviewTest extends \Tests\TestCase
{
    public function test_local_demo_shell_is_available_without_an_admin_session(): void
    {
        $this->app['env'] = 'local';
        $this->get('/signage/demo')->assertOk()->assertSee('Gyros City');
    }

    public function test_local_preview_assets_follow_the_served_checkout_with_a_stale_asset_url(): void
    {
        $this->app['env'] = 'local';
        config(['app.asset_url'=>'http://localhost/old-checkout']);
        $this->get('/signage/demo')->assertOk()->assertDontSee('old-checkout')->assertSee('/public/signage/renderer.js');
    }
    public function test_local_demo_is_not_exposed_in_production_or_to_remote_visitors(): void
    {
        $this->app['env'] = 'production';
        $this->get('/signage/demo')->assertNotFound();
        $this->app['env'] = 'local';
        $this->withServerVariables(['REMOTE_ADDR'=>'203.0.113.10'])->get('/signage/demo')->assertNotFound();
    }
}
