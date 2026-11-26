<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Widgets\SalesStats;
use App\Jobs\SubmitOrderToMart;
use App\Livewire\CartPage;
use App\Livewire\CheckoutPage;
use App\Livewire\TrackOrder;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Town;
use App\Models\User;
use App\Services\Cart;
use App\Services\Checkout;
use App\Services\CheckoutException;
use App\Services\OrderApproval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Sleep;
use Livewire\Livewire;
use Tests\TestCase;

/** Regressions from the 2026-11-26 audit. */
class HardeningTest extends TestCase
{
    use RefreshDatabase;

    private function order(array $extra = []): Order
    {
        $order = Order::create($extra + [
            'reference' => Order::newReference(), 'first_name' => 'a', 'last_name' => 'b', 'phone' => '0599000000',
            'gender' => 'male', 'town_id' => 673, 'district_name' => 'd', 'town_name' => 't', 'address_line1' => 'x',
            'status' => 'pending_review', 'products_total' => 120, 'shipping_fee' => 20, 'customer_total' => 140,
        ]);
        $order->items()->create(['product_id' => Product::factory()->create()->id, 'name' => 'p', 'quantity' => 1, 'unit_price' => 120, 'unit_cost' => 100]);

        return $order->fresh();
    }

    private function checkout(string $phone)
    {
        Town::firstOrCreate(['id' => 673], ['district_id' => 254, 'district_name' => 'd', 'name' => 't', 'available' => true,
            'fees' => [['from_total' => 0, 'to_total' => null, 'fee' => 20]]]);
        app(Cart::class)->add(Product::factory()->create()->id, null);

        return Livewire::test(CheckoutPage::class)->set('first_name', 'a')->set('last_name', 'b')->set('phone', $phone)
            ->set('gender', 'male')->set('district_id', '254')->set('town_id', '673')->set('address_line1', 'x')->call('place');
    }

    public function test_order_cannot_be_rejected_while_it_is_being_approved(): void
    {
        Bus::fake();
        $order = $this->order();
        $refused = false;
        Http::fake(['*/orders/validate' => function () use ($order, &$refused) {
            try {
                app(OrderApproval::class)->reject(Order::find($order->id)); // second tab
            } catch (CheckoutException) {
                $refused = true;
            }

            return Http::response(['data' => ['validation' => ['quote' => ['products_total' => 120, 'shipping_fee' => 20, 'free_shipping' => false, 'cod_total' => 140]]]]);
        }]);
        $stale = Order::find($order->id);

        app(OrderApproval::class)->approve($order);

        $this->assertTrue($refused);
        $this->assertSame(OrderStatus::Approved, $order->fresh()->status);
        $this->expectException(CheckoutException::class); // a stale copy still reading "pending" is refused too
        app(OrderApproval::class)->reject($stale);
    }

    public function test_order_sync_resumes_where_it_stopped(): void
    {
        Sleep::fake();
        [$first, $second] = [$this->order()->reference, $this->order()->reference];
        $at = ['2026-11-25T10:00:00+02:00', '2026-11-25T11:00:00+02:00'];
        Http::fake([
            '*/orders?*' => Http::response(['data' => ['orders' => [
                ['partner_reference' => $first, 'updated_at' => $at[0]], ['partner_reference' => $second, 'updated_at' => $at[1]],
            ]], 'meta' => ['pagination' => ['has_next' => false]]]),
            "*/orders/$first" => Http::response(['data' => ['order' => ['status' => ['id' => 1, 'name' => 'x']]]]),
            "*/orders/$second" => Http::response(['error' => ['code' => 'rate_limit_exceeded']], 429, ['Retry-After' => '30']),
        ]);

        $this->artisan('mart:sync-orders')->assertFailed();

        $this->assertSame($at[0], Setting::get('orders_synced_at'));
        $this->assertSame(OrderStatus::Submitted, Order::where('reference', $first)->value('status'));
        Sleep::assertSleptTimes(2);
    }

    public function test_reply_without_an_order_is_not_counted_as_sent(): void
    {
        $order = $this->order(['status' => OrderStatus::Approved, 'request_body' => ['x' => 1]]);
        Http::fake(['*' => Http::response(['data' => []])]);

        SubmitOrderToMart::dispatchSync($order->id);

        $this->assertSame(OrderStatus::Approved, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->next_attempt_at);
    }

    public function test_rejected_key_still_takes_the_order(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 'invalid_api_key']], 401)]);

        $this->checkout('0599000000');

        $this->assertFalse(Order::sole()->quote_verified);
    }

    public function test_menu_count_includes_warnings_until_handled(): void
    {
        $this->order();
        $this->order(['status' => OrderStatus::SendFailed]);
        $mismatch = $this->order(['status' => OrderStatus::Submitted]);
        $mismatch->applyMart(['cod_total' => 999]);
        $this->order(['status' => OrderStatus::Submitted]);
        $this->order(['status' => OrderStatus::Approved, 'send_attempts' => 4]);
        $this->order(['status' => OrderStatus::Approved, 'send_attempts' => 5]); // stuck

        $this->assertSame('4', OrderResource::getNavigationBadge());

        app(OrderApproval::class)->markHandled($mismatch);
        $mismatch->applyMart(['cod_total' => 999]); // the same figure stays quiet
        $this->assertSame('3', OrderResource::getNavigationBadge());

        $mismatch->applyMart(['cod_total' => 20]); // a new one does not
        $this->assertSame('total_mismatch', $mismatch->fresh()->attention_reason);

        $mismatch->applyMart(['cancellation' => ['cancelled_at' => 'now']]);
        $this->assertNull($mismatch->fresh()->attention_reason);
    }

    public function test_handled_button_shows_only_for_a_warning_and_clears_it(): void
    {
        $this->actingAs(User::factory()->create());
        $quiet = $this->order(['status' => OrderStatus::Submitted]);
        $warned = $this->order(['status' => OrderStatus::Submitted, 'attention_reason' => 'total_mismatch']);

        Livewire::test(ViewOrder::class, ['record' => $quiet->id])->assertActionHidden('markHandled');
        Livewire::test(ViewOrder::class, ['record' => $warned->id])->callAction('markHandled');

        $this->assertNull($warned->fresh()->attention_reason);
        $this->get('/admin/profile')->assertOk(); // the owner can change their password
    }

    public function test_order_sync_stops_on_a_refusal_but_skips_a_missing_order(): void
    {
        $reference = $this->order()->reference;
        $answer = 'insufficient_scope';
        Http::fake([
            '*/orders?*' => Http::response(['data' => ['orders' => [['partner_reference' => $reference, 'updated_at' => '2026-11-25T10:00:00+02:00']]], 'meta' => ['pagination' => ['has_next' => false]]]),
            "*/orders/$reference" => function () use (&$answer) {
                return Http::response(['error' => ['code' => $answer]], $answer === 'order_not_found' ? 404 : 403);
            },
        ]);

        $this->artisan('mart:sync-orders')->assertFailed();
        $this->assertNull(Setting::get('orders_synced_at'));

        $answer = 'order_not_found';
        $this->artisan('mart:sync-orders')->assertSuccessful();
        $this->assertNotNull(Setting::get('orders_synced_at'));
    }

    public function test_an_outage_is_asked_about_only_once(): void
    {
        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;

            return Http::response('down', 503);
        });
        $checkout = app(Checkout::class);
        $this->assertFalse($checkout->freeShippingAllowed());
        $this->assertFalse($checkout->freeShippingAllowed());
        $this->assertSame(1, $calls);
    }

    public function test_blocked_account_refuses_the_order(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 'account_closed']], 403)]);

        $this->checkout('0599000000');

        $this->assertSame(0, Order::count());
    }

    public function test_only_one_send_job_waits_per_order(): void
    {
        Queue::fake();

        SubmitOrderToMart::dispatch(7);
        SubmitOrderToMart::dispatch(7);
        SubmitOrderToMart::dispatch(8);

        Queue::assertPushed(SubmitOrderToMart::class, 2);
    }

    public function test_failed_sends_are_not_sales_and_empty_pages_say_so(): void
    {
        $this->order(['status' => OrderStatus::SendFailed]);
        Setting::set('page_about', '<p>&nbsp;</p>');

        $this->assertSame(0, SalesStats::figures('30')['orders']);
        $this->get('/pages/about')->assertSee(__('store.page_soon'));
    }

    public function test_phone_needs_digits_and_persian_digits_track(): void
    {
        Http::fake(['*' => Http::response('down', 503)]);
        $this->checkout('-------')->assertHasErrors('phone');
        $order = $this->order();

        Livewire::test(TrackOrder::class)->set('reference', $order->reference)->set('phone', '۰۵۹۹۰۰۰۰۰۰')->call('find')
            ->assertNotSet('result', null);
    }

    public function test_cleared_setting_stays_cleared(): void
    {
        $this->assertNotNull(Setting::get('footer_text'));

        Setting::set('footer_text', null);

        $this->assertNull(Setting::get('footer_text'));
    }

    public function test_settings_are_read_once_per_request(): void
    {
        foreach (range(1, 10) as $i) {
            Category::create(['id' => $i, 'name' => "c$i"]);
            Product::factory()->create(['category_id' => $i, 'cover_image' => 'https://x.test/a.jpg']);
        }
        Setting::set('listing_categories', range(1, 10));
        DB::enableQueryLog();

        $this->get('/')->assertOk();

        $log = collect(DB::getQueryLog())->pluck('query');
        $this->assertLessThanOrEqual(1, $log->filter(fn ($q) => str_contains($q, '"settings"'))->count());
        $this->assertLessThan(30, $log->count());
    }

    public function test_emptied_quantity_keeps_the_line(): void
    {
        $id = Product::factory()->create()->id;
        app(Cart::class)->add($id, null, 3);

        Livewire::test(CartPage::class)->call('setQuantity', "$id:", '');

        $this->assertSame(["$id:" => 3], app(Cart::class)->raw());
    }

    public function test_town_sync_maps_districts_and_retires_missing_towns(): void
    {
        Town::create(['id' => 1, 'district_id' => 9, 'district_name' => 'old', 'name' => 'gone', 'available' => true, 'fees' => []]);
        Http::fake(['*/states' => Http::response(['data' => ['cities' => [['id' => 248, 'name' => ' القدس ', 'states' => [
            ['id' => 2, 'name' => 'البلدة', 'delivery' => ['available' => true, 'fees' => [['from_total' => 0, 'to_total' => null, 'fee' => 30]]]],
        ]]]]])]);

        $this->artisan('mart:sync-towns')->assertSuccessful();

        $town = Town::find(2);
        $this->assertSame([248, 'القدس', 30.0, 'Jerusalem'], [$town->district_id, $town->district_name, $town->fee(10), $town->zone()]);
        $this->assertFalse(Town::find(1)->available);
    }
}
