<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = ['/admin', '/admin/orders', '/admin/products', '/admin/categories', '/admin/manage-settings'];

    public function test_guests_cannot_reach_any_admin_page(): void
    {
        foreach (self::PAGES as $page) {
            $this->get($page)->assertRedirect('/admin/login');
        }
    }

    public function test_admin_pages_render(): void
    {
        $this->actingAs(User::factory()->create());
        $order = $this->order();

        foreach ([...self::PAGES, "/admin/orders/{$order->id}"] as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_override_below_cost_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create();
        $action = TestAction::make('price')->table($product);

        Livewire::test(ListProducts::class)->callAction($action, ['price_override' => 99])->assertHasFormErrors(['price_override']);
        $this->assertNull($product->fresh()->price_override);

        Livewire::test(ListProducts::class)->callAction($action, ['price_override' => 150])->assertHasNoFormErrors();
        $this->assertEquals(150, $product->fresh()->selling_price);
    }

    public function test_admin_can_reject_from_the_order_page(): void
    {
        $this->actingAs(User::factory()->create());
        Http::fake();
        $order = $this->order();

        Livewire::test(ViewOrder::class, ['record' => $order->id])->callAction('reject');

        $this->assertSame('rejected', $order->fresh()->status->value);
    }

    public function test_admin_approves_from_the_order_page_and_must_confirm_a_new_total(): void
    {
        $this->actingAs(User::factory()->create());
        Product::factory()->create(['id' => 1]);
        Http::fake([
            '*/orders/validate' => fn ($r) => Http::response(['data' => ['validation' => ['quote' => [
                'products_total' => $r['products'][0]['price'], 'shipping_fee' => 20, 'free_shipping' => false,
                'cod_total' => $r['products'][0]['price'] + 20, 'reward' => ['net' => 20],
            ]]]]),
            '*/api/v1/orders' => Http::response(['data' => ['order' => ['status' => 'processing', 'cod_total' => 150]]]),
        ]);
        $order = $this->order();
        $price = 'price_'.$order->items()->value('id');

        $page = Livewire::test(ViewOrder::class, ['record' => $order->id]);
        $page->callAction('approve', [$price => 130, 'accepted' => false]);
        $this->assertSame('pending_review', $order->fresh()->status->value);

        $page = Livewire::test(ViewOrder::class, ['record' => $order->id]);
        $page->callAction('approve', [$price => 130, 'accepted' => true])->assertHasNoFormErrors();

        $this->assertSame('submitted', $order->fresh()->status->value);
        $this->assertEquals(150, $order->fresh()->customer_total);
        $this->assertEquals(130, $order->items()->value('unit_price'));
    }

    private function order(): Order
    {
        $order = Order::create([
            'reference' => Order::newReference(), 'first_name' => 'a', 'last_name' => 'b', 'phone' => '0599000000',
            'gender' => 'male', 'town_id' => 673, 'district_name' => 'd', 'town_name' => 't', 'address_line1' => 'x',
            'status' => 'pending_review', 'products_total' => 120, 'shipping_fee' => 20, 'customer_total' => 140,
        ]);
        $order->items()->create(['product_id' => 1, 'name' => 'p', 'quantity' => 1, 'unit_price' => 120, 'unit_cost' => 100]);

        return $order;
    }
}
