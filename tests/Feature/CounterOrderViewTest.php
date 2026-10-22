<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderDetails;
use Tests\TestCase;

class CounterOrderViewTest extends TestCase
{
    public function test_saved_customizations_are_visible_with_removals_overriding_their_source(): void
    {
        $orders = new OrderDetails();
        $orders->forceFill(['addons_name' => 'Rice| --- Onion | Garlic sauce||', 'extras_name' => 'Cheese|--- Tomato']);
        $html = view('admin.orders.item-customizations', compact('orders'))->render();
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        $this->assertSame(2, $xpath->query('//li[contains(@class,"--removed")]')->length);
        $this->assertSame(1, $xpath->query('//li[contains(@class,"--extra")]')->length);
        $this->assertSame(2, $xpath->query('//li[contains(@class,"--choice")]')->length);
        $this->assertStringContainsString('Nem kéri:', $html);
        $this->assertStringContainsString('Rice', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('modal', $html);
    }

    public function test_customization_names_are_escaped_and_empty_snapshots_do_not_show_empty_lists(): void
    {
        $orders = new OrderDetails();
        $orders->forceFill(['addons_name' => '<img src=x onerror=alert(1)>', 'extras_name' => 'Chef\'s cheese']);
        $html = view('admin.orders.item-customizations', compact('orders'))->render();
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $orders->forceFill(['addons_name' => null, 'extras_name' => ' |  | ']);
        $this->assertSame('', trim(view('admin.orders.item-customizations', compact('orders'))->render()));
    }

    public function test_open_orders_can_change_status_while_terminal_orders_only_show_their_label(): void
    {
        $orderdata = new Order();
        $orderdata->forceFill(['id'=>7,'status'=>10,'status_type'=>2,'order_number'=>'QA-7']);
        $statusOptions = collect([(object) ['id'=>10,'type'=>2,'name'=>'Accepted'], (object) ['id'=>11,'type'=>2,'name'=>'Ready']]);
        $orderStatusName = 'Accepted';
        $html = view('admin.orders.status-control', compact('orderdata','statusOptions','orderStatusName'))->render();
        $this->assertStringContainsString('data-order-id="7"', $html);
        $this->assertStringContainsString('value="11" data-type="2"', $html);
        $this->assertSame(1, substr_count($html, 'value="10"'));
        foreach ([3,4] as $type) {
            $orderdata->status_type = $type;
            $html = view('admin.orders.status-control', compact('orderdata','statusOptions','orderStatusName'))->render();
            $this->assertStringNotContainsString('<select', $html);
            $this->assertStringContainsString('Accepted', $html);
        }
    }
}
