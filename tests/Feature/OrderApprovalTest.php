<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Jobs\SubmitOrderToMart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\CheckoutException;
use App\Services\OrderApproval;
use App\Services\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function order(array $attributes = []): Order
    {
        $product = Product::factory()->create();
        $order = Order::create($attributes + [
            'reference' => Order::newReference(), 'first_name' => 'a', 'last_name' => 'b', 'phone' => '0599000000',
            'gender' => 'male', 'town_id' => 673, 'district_name' => 'd', 'town_name' => 't', 'address_line1' => 'x',
            'status' => OrderStatus::PendingReview, 'products_total' => 120, 'shipping_fee' => 20, 'customer_total' => 140,
        ]);
        $order->items()->create(['product_id' => $product->id, 'name' => $product->name, 'quantity' => 1, 'unit_price' => 120, 'unit_cost' => 100]);

        return $order->fresh();
    }

    private function mart(mixed $create, float $quoteFee = 20, array $extra = []): void
    {
        Http::fake($extra + [
            '*/orders/validate' => fn ($r) => Http::response(['data' => ['validation' => ['quote' => [
                'products_total' => $r['products'][0]['price'], 'shipping_fee' => $quoteFee, 'free_shipping' => false,
                'cod_total' => $r['products'][0]['price'] + $quoteFee, 'reward' => ['net' => 20],
            ]]]]),
            '*/api/v1/orders' => $create,
        ]);
    }

    private function created(float $codTotal = 140, bool $replay = false)
    {
        return Http::response(['data' => ['order' => ['status' => 'processing', 'cod_total' => $codTotal]], 'meta' => ['idempotency_replay' => $replay]]);
    }

    private function approval(): OrderApproval
    {
        return app(OrderApproval::class);
    }

    private function sends(): array
    {
        return Http::recorded(fn ($r) => str_ends_with($r->url(), '/api/v1/orders'))->map(fn ($pair) => $pair[0]->body())->values()->all();
    }

    public function test_approval_sends_once_and_keeps_the_agreed_price_after_a_markup_change(): void
    {
        $order = $this->order();
        $this->mart($this->created());
        Setting::set('markup_percent', 80);
        Pricing::recalculate();

        $this->approval()->approve($order);
        SubmitOrderToMart::dispatchSync($order->id); // a second run does nothing

        $this->assertSame(OrderStatus::Submitted, $order->fresh()->status);
        $this->assertNull($order->fresh()->attention_reason);
        $this->assertCount(1, $this->sends());
        $this->assertStringContainsString('"price":120', $this->sends()[0]);
    }

    public function test_retry_after_failure_resends_the_identical_body(): void
    {
        $order = $this->order();
        $this->mart(Http::sequence()->push('down', 502)->pushResponse($this->created(replay: true)));

        $this->approval()->approve($order);
        $this->assertSame(OrderStatus::Approved, $order->fresh()->status);

        $this->travel(2)->hours();
        $this->artisan('mart:resend-orders');

        $this->assertSame(OrderStatus::Submitted, $order->fresh()->status);
        $this->assertCount(2, $this->sends());
        $this->assertSame($this->sends()[0], $this->sends()[1]);
    }

    public function test_refusal_and_conflict_stop_sending(): void
    {
        $order = $this->order();
        $this->mart(Http::response(['error' => ['code' => 'idempotency_conflict']], 409));

        $this->approval()->approve($order);

        $this->assertSame(OrderStatus::SendFailed, $order->fresh()->status);
        $this->assertSame('conflict', $order->fresh()->attention_reason);
        $this->expectException(CheckoutException::class);
        $this->approval()->approve($order->fresh());
    }

    public function test_changed_total_needs_customer_acceptance(): void
    {
        Bus::fake();
        $order = $this->order();
        $this->mart($this->created(), quoteFee: 30);

        try {
            $this->approval()->approve($order);
            $this->fail('approved without acceptance');
        } catch (CheckoutException $e) {
            $this->assertSame(150.0, $e->figures['customer_total']);
        }
        $this->assertSame(OrderStatus::PendingReview, $order->fresh()->status);
        Bus::assertNothingDispatched();

        $this->approval()->approve($order->fresh(), customerAccepted: true);
        $this->assertEquals(150, $order->fresh()->customer_total);
    }

    public function test_price_below_cost_blocks_approval(): void
    {
        Bus::fake();
        $order = $this->order();
        Product::query()->first()->update(['cost_price' => 130]);
        $this->mart($this->created());

        $this->expectException(CheckoutException::class);
        $this->approval()->approve($order);
    }

    public function test_created_total_mismatch_is_flagged(): void
    {
        $order = $this->order();
        $this->mart($this->created(codTotal: 145));

        $this->approval()->approve($order);

        $this->assertSame('total_mismatch', $order->fresh()->attention_reason);
    }

    public function test_rejected_order_is_never_sent(): void
    {
        $order = $this->order();
        $this->mart($this->created());

        $this->approval()->reject($order);
        SubmitOrderToMart::dispatchSync($order->id);

        $this->assertSame([], $this->sends());
    }

    public function test_stop_retrying_only_when_mart_does_not_have_it(): void
    {
        $order = $this->order(['status' => OrderStatus::Approved, 'request_body' => ['x' => 1]]);
        Http::fake(['*/orders/'.$order->reference => Http::response(['error' => ['code' => 'order_not_found']], 404)]);

        $this->approval()->stopRetrying($order);

        $this->assertSame(OrderStatus::PendingReview, $order->fresh()->status);
    }

    public function test_cancellation_in_mart_is_picked_up(): void
    {
        $order = $this->order(['status' => OrderStatus::Submitted]);
        Http::fake([
            '*/orders?*' => Http::response(['data' => ['orders' => [['partner_reference' => $order->reference]]], 'meta' => ['pagination' => ['has_next' => false]]]),
            '*/orders/'.$order->reference => Http::response(['data' => ['order' => ['status' => ['id' => 6, 'name' => 'Canceled'], 'cancellation' => ['cancelled_at' => '2026-11-25T10:00:00Z']]]]),
        ]);

        $this->artisan('mart:sync-orders')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }
}
