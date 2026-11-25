<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Widgets\SalesStats;
use App\Livewire\TrackOrder;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    private function order(OrderStatus $status, float $total, ?string $earning = null): Order
    {
        return Order::create([
            'reference' => Order::newReference(), 'first_name' => 'a', 'last_name' => 'b', 'phone' => '0599 000 000',
            'gender' => 'male', 'town_id' => 673, 'district_name' => 'd', 'town_name' => 't', 'address_line1' => 'x',
            'status' => $status, 'products_total' => $total, 'shipping_fee' => 0, 'customer_total' => $total,
            'expected_net' => 10, 'net_earning' => 10, 'earning_state' => $earning,
        ]);
    }

    public function test_lookup_needs_the_matching_phone(): void
    {
        $order = $this->order(OrderStatus::PendingReview, 100);

        Livewire::test(TrackOrder::class)->set('reference', $order->reference)->set('phone', '0599111111')->call('find')
            ->assertSet('result', null)->assertSet('notice', __('store.track_not_found'));
        Livewire::test(TrackOrder::class)->set('reference', 'TW-NOPE')->set('phone', '0599000000')->call('find')
            ->assertSet('notice', __('store.track_not_found'));
        Livewire::test(TrackOrder::class)->set('reference', $order->reference)->set('phone', '0599000000')->call('find')
            ->assertSee(__('orders.status.pending_review'));
    }

    public function test_dashboard_excludes_rejected_and_cancelled(): void
    {
        $this->order(OrderStatus::Submitted, 100, 'available');
        $this->order(OrderStatus::PendingReview, 50);
        $this->order(OrderStatus::Rejected, 999);
        $this->order(OrderStatus::Cancelled, 999);

        $this->assertSame(['orders' => 2, 'sales' => 150.0, 'expected' => 20.0, 'delivered' => 10.0], SalesStats::figures('30'));
    }
}
