<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Livewire\CheckoutPage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Town;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use App\Services\Cart;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Town::create(['id' => 673, 'district_id' => 254, 'district_name' => 'اريحا', 'name' => 'أريحا', 'available' => true,
            'fees' => [['from_total' => 0, 'to_total' => 300, 'fee' => 20], ['from_total' => 300, 'to_total' => null, 'fee' => 10]]]);
        Town::create(['id' => 800, 'district_id' => 248, 'district_name' => 'القدس', 'name' => 'القدس', 'available' => true,
            'fees' => [['from_total' => 0, 'to_total' => null, 'fee' => 30]]]);
    }

    /** Mart's quote for the body it was sent: cost 100, fee 20. */
    public static function fakeMart(float $fee = 20): void
    {
        Http::fake([
            '*/account' => Http::response(['data' => ['account' => ['free_shipping_allowed' => true]]]),
            '*/orders/validate' => function ($request) use ($fee) {
                $total = collect($request['products'])->sum(fn ($p) => $p['price'] * $p['quantity']);
                $free = $request['free_shipping'];

                return Http::response(['data' => ['validation' => ['quote' => [
                    'products_total' => $total, 'shipping_fee' => $fee, 'free_shipping' => $free,
                    'cod_total' => $total + ($free ? 0 : $fee), 'reward' => ['net' => 20],
                ]]]]);
            },
        ]);
    }

    private function fill(Product $product, int $quantity = 1)
    {
        app(Cart::class)->add($product->id, null, $quantity);

        return Livewire::test(CheckoutPage::class)
            ->set('first_name', 'سارة')->set('last_name', 'خليل')->set('phone', '٠٥٩٩٠٠٠٠٠٠')->set('gender', 'female')
            ->set('district_id', '254')->set('town_id', '673')->set('address_line1', 'الشارع الرئيسي');
    }

    public function test_fee_bracket_boundary(): void
    {
        $town = Town::find(673);
        $this->assertSame(20.0, $town->fee(299.99));
        $this->assertSame(10.0, $town->fee(300));
    }

    public function test_free_delivery_only_when_threshold_and_margin_allow(): void
    {
        self::fakeMart();
        $product = Product::factory()->create();
        Setting::set('free_from', ['default' => 100, 'Jerusalem' => 100, 'Isreal' => 100]);

        $this->fill($product)->assertSet('shownTotal', 120.0); // margin 20 covers fee 20

        app(Cart::class)->clear();
        Setting::set('markup_percent', 0);
        $product->save();
        $this->fill($product)->assertSet('shownTotal', 120.0); // 100 + fee: no margin
    }

    public function test_order_is_stored_for_review_and_not_sent(): void
    {
        self::fakeMart();
        $product = Product::factory()->create();

        $this->fill($product)->call('place')->assertHasNoErrors()->assertRedirect();

        $order = Order::with('items')->sole();
        $this->assertSame(OrderStatus::PendingReview, $order->status);
        $this->assertSame('0599000000', $order->phone);
        $this->assertSame(['أريحا', 'اريحا'], [$order->town_name, $order->district_name]);
        $this->assertEquals(140, $order->customer_total);
        $this->assertEquals(120, $order->items[0]->unit_price);
        $this->assertTrue($order->quote_verified);
        $this->assertSame([], app(Cart::class)->raw());
        Http::assertNotSent(fn ($r) => str_ends_with($r->url(), '/api/v1/orders'));
        $this->get("/orders/{$order->reference}/done")->assertOk();
    }

    public function test_every_admin_is_emailed_in_their_language_without_customer_details(): void
    {
        Notification::fake();
        self::fakeMart();
        $arabic = User::factory()->create(['locale' => 'ar']);
        $english = User::factory()->create(['locale' => 'en']);

        $this->fill(Product::factory()->create())->call('place');

        $order = Order::sole();
        Notification::assertSentTo([$arabic, $english], NewOrderReceived::class);
        Notification::assertCount(2);

        foreach ([[$arabic, 'ar', 'rtl'], [$english, 'en', 'ltr']] as [$user, $locale, $direction]) {
            app()->setLocale($user->preferredLocale());
            $mail = (new NewOrderReceived($order))->toMail($user);
            $html = view($mail->view, $mail->viewData)->render();

            $this->assertSame(__('admin.mail.subject', ['reference' => $order->reference], $locale), $mail->subject);
            $this->assertStringContainsString("dir=\"$direction\"", $html);
            $this->assertStringContainsString("/admin/orders/{$order->id}", $html);
            $this->assertStringNotContainsString($order->phone, $html);
            $this->assertStringNotContainsString($order->first_name, $html);
        }
    }

    public function test_a_broken_queue_does_not_lose_the_order(): void
    {
        self::fakeMart();
        User::factory()->create();
        Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Redis is down'));

        $this->fill(Product::factory()->create())->call('place')->assertRedirect();

        $this->assertSame(1, Order::count());
        $this->assertSame([], app(Cart::class)->raw());
    }

    public function test_changed_total_needs_a_second_confirmation(): void
    {
        self::fakeMart(fee: 25);
        $page = $this->fill(Product::factory()->create())->call('place');

        $this->assertSame(0, Order::count());
        $this->assertArrayNotHasKey('expected_net', $page->get('confirmed'));
        $page->assertSet('shownTotal', 145.0)->call('place');
        $this->assertEquals(145, Order::sole()->customer_total);
    }

    public function test_stale_price_is_re_read_and_shown_before_ordering(): void
    {
        $product = Product::factory()->create(); // cost 100, sells 120
        Http::fake([
            '*/account' => Http::response(['data' => ['account' => ['free_shipping_allowed' => true]]]),
            '*/orders/validate' => Http::response(['error' => ['code' => 'price_below_minimum']], 422),
            '*/products?*' => Http::response(['data' => ['products' => [CatalogTest::martProduct($product->id, 150)]]]),
        ]);

        $this->fill($product)->call('place')->assertSet('notice', __('store.prices_updated'))->assertSet('shownTotal', 200.0);

        $this->assertEquals(180, $product->fresh()->selling_price);
        $this->assertSame(0, Order::count());
    }

    public function test_unreachable_mart_still_accepts_as_unverified(): void
    {
        Http::fake(['*' => Http::response('down', 503)]);

        $this->fill(Product::factory()->create())->call('place');

        $this->assertFalse(Order::sole()->quote_verified);
    }

    public function test_town_must_belong_to_district(): void
    {
        self::fakeMart();

        $this->fill(Product::factory()->create())->set('town_id', '800')->call('place')->assertHasErrors('town_id');
        $this->assertSame(0, Order::count());
    }

    public function test_one_phone_is_limited_to_three_orders_an_hour(): void
    {
        self::fakeMart();
        $product = Product::factory()->create();

        foreach (range(1, 4) as $i) {
            $page = $this->fill($product)->call('place');
        }

        $this->assertSame(3, Order::count());
        $page->assertSet('notice', __('store.too_many'));
    }

    public function test_ordering_switched_off(): void
    {
        self::fakeMart();
        Setting::set('ordering_enabled', false);
        app(Cart::class)->add(Product::factory()->create()->id, null);

        Livewire::test(CheckoutPage::class)->assertSee(Setting::get('closed_message'))->call('place');
        $this->assertSame(0, Order::count());
    }
}
