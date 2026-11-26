<?php

namespace Tests\Feature;

use App\Livewire\Catalog;
use App\Livewire\Home;
use App\Livewire\ProductPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Cart;
use App\Services\Pricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public static function martProduct(int $id, float $cost = 100, array $extra = []): array
    {
        return array_replace([
            'product_id' => $id, 'reference' => "R$id", 'name' => "منتج $id", 'status' => 'active', 'brand' => null,
            'category' => null, 'price' => ['group_price' => $cost], 'total_quantity' => 3, 'in_stock' => true,
            'cover_image' => null, 'images' => [], 'combinations' => [], 'description' => '', 'description_short' => '',
        ], $extra);
    }

    private function page(array $products, bool $hasNext = false): array
    {
        return ['data' => ['products' => $products], 'meta' => ['pagination' => ['has_next' => $hasNext]]];
    }

    public function test_price_never_drops_below_cost(): void
    {
        $this->assertSame(120.0, Pricing::selling(100));
        $this->assertSame(100.0, Pricing::selling(100, 50));
        $this->assertSame(150.0, Pricing::selling(100, 150));

        $product = Product::factory()->create(['price_override' => 110]);
        $product->update(['cost_price' => 130]);
        $this->assertEquals(130, $product->selling_price);

        Product::factory()->create(['id' => 7]);
        Setting::set('markup_percent', 50);
        Pricing::recalculate();
        $this->assertEquals(150, Product::find(7)->selling_price);
        $this->assertEquals(130, $product->fresh()->selling_price);
    }

    public function test_sync_keeps_admin_fields_and_marks_missing_products(): void
    {
        Product::factory()->create(['id' => 1, 'price_override' => 175, 'hidden' => true, 'featured' => true]);
        Product::factory()->create(['id' => 2]);
        Http::fake([
            '*/categories' => Http::response(['data' => ['categories' => [['category_id' => 9, 'category_name' => 'a > b']]]]),
            '*/products*' => Http::response($this->page([self::martProduct(1, 90)])),
        ]);

        $this->artisan('mart:sync-catalog')->assertSuccessful();

        $kept = Product::find(1);
        $this->assertEquals(175, $kept->selling_price);
        $this->assertTrue($kept->hidden && $kept->featured);
        $this->assertEquals(90, $kept->cost_price);
        $this->assertNotNull(Product::find(2)->removed_at);
        $this->assertNotNull(Setting::get('sync_last_success'));
    }

    public function test_quick_pass_updates_stock_and_price_but_keeps_descriptions(): void
    {
        Product::factory()->create(['id' => 1, 'description' => 'وصف']);
        $row = self::martProduct(1, 200, ['total_quantity' => 0, 'in_stock' => false]);
        unset($row['description'], $row['description_short']);
        Http::fake(['*/products*' => Http::response($this->page([$row]))]);

        $this->artisan('mart:sync-catalog --quick')->assertSuccessful();

        $product = Product::find(1);
        $this->assertSame(['وصف', 0], [$product->description, $product->total_quantity]);
        $this->assertEquals(240, $product->selling_price);
        Http::assertSentCount(1); // no categories
        Http::assertSent(fn ($request) => ! str_contains($request->url(), 'include='));
    }

    public function test_failed_page_removes_nothing(): void
    {
        Sleep::fake();
        Product::factory()->create(['id' => 2]);
        Http::fake([
            '*/categories' => Http::response(['data' => ['categories' => []]]),
            '*page=1&*' => Http::response($this->page([self::martProduct(1)], true)),
            '*page=2&*' => Http::response('boom', 500),
        ]);

        $this->artisan('mart:sync-catalog')->assertFailed();

        $this->assertNull(Product::find(2)->removed_at);
        $this->assertNotNull(Setting::get('sync_last_error'));
    }

    public function test_a_zero_cost_product_is_not_for_sale(): void
    {
        $free = Product::factory()->create(['cost_price' => 0]);

        $this->assertFalse($free->isVisible());
        $this->assertSame(0, Product::visible()->count());
        $this->get('/products/'.$free->id)->assertNotFound();

        $free->update(['price_override' => 10]); // the owner may still price it
        $this->assertTrue($free->isVisible());
    }

    public function test_search_ignores_hamza_and_hides_hidden_products(): void
    {
        Product::factory()->create(['name' => 'أحذية رياضية']);
        $hidden = Product::factory()->create(['name' => 'احذية مخفية', 'hidden' => true]);

        foreach (['احذية', 'أحذيه'] as $term) {
            Livewire::test(Catalog::class)->set('q', $term)->assertSee('أحذية رياضية')->assertDontSee('احذية مخفية');
        }

        $this->get('/products/'.$hidden->id)->assertNotFound();
    }

    public function test_paging_moves_and_a_new_filter_returns_to_page_one(): void
    {
        Product::factory()->count(30)->create();

        Livewire::test(Catalog::class)->call('nextPage')->assertSet('paginators.page', 2)
            ->set('available', true)->assertSet('paginators.page', 1);
    }

    public function test_categories_show_arabic_names_and_sync_keeps_the_admins_own(): void
    {
        $shoes = Category::create(['id' => 9, 'name' => "clothing & accessories > shoes & footwear > men's shoes"]);
        $new = Category::create(['id' => 10, 'name' => 'a > something new']);
        $this->assertSame('أحذية رجالية', $shoes->label());
        $this->assertSame('something new', $new->label());

        $shoes->update(['name_ar' => 'أحذية الرجال']);
        Http::fake([
            '*/categories' => Http::response(['data' => ['categories' => [['category_id' => 9, 'category_name' => $shoes->name]]]]),
            '*/products*' => Http::response($this->page([])),
        ]);
        $this->artisan('mart:sync-catalog')->assertSuccessful();

        $this->assertSame('أحذية الرجال', Category::find(9)->label());
        $this->get('/categories/9')->assertSee('أحذية الرجال');
    }

    public function test_search_finds_products_by_arabic_category_name(): void
    {
        Category::create(['id' => 1, 'name' => 'health & beauty > beauty > fragrances']);
        Category::create(['id' => 2, 'name' => "health & beauty > beauty > fragrances > men's fragrances"]);
        Product::factory()->create(['name' => 'Oud 100ml', 'category_id' => 2]);
        Product::factory()->create(['name' => 'Hammer', 'category_id' => null]);

        // Own name, an ancestor's name with hamza dropped, and category word plus product word.
        foreach (['عطور رجالية', 'الصحه', 'العطور oud'] as $term) {
            Livewire::test(Catalog::class)->set('q', $term)->assertSee('Oud 100ml')->assertDontSee('Hammer');
        }

        Category::find(1)->update(['name_ar' => 'روائح']);
        Livewire::test(Catalog::class)->set('q', 'روائح')->assertSee('Oud 100ml');
        Livewire::test(Catalog::class)->set('q', 'روائح hammer')->assertDontSee('Oud 100ml');
    }

    public function test_home_slider_shows_only_products_that_can_be_bought(): void
    {
        $image = ['cover_image' => 'https://img.test/a.jpg'];
        $ok = Product::factory()->create($image);
        // Hidden, out of stock, and without a picture.
        [
            Product::factory()->create($image + ['hidden' => true]),
            Product::factory()->create($image + ['total_quantity' => 0]),
            Product::factory()->create(),
        ];

        $slides = Livewire::test(Home::class)->viewData('slides');

        $this->assertSame([$ok->id], $slides->pluck('id')->all());
    }

    public function test_adding_to_cart_toasts_and_never_exceeds_stock(): void
    {
        $product = Product::factory()->create(['total_quantity' => 2]);

        Livewire::test(ProductPage::class, ['id' => $product->id])
            ->set('quantity', 2)->call('addToCart')->assertDispatched('toast')->assertDispatched('cart-updated')
            ->set('quantity', 1)->call('addToCart')->assertNotDispatched('toast')->assertSee(__('store.not_enough_stock'));

        $this->assertSame([$product->id.':' => 2], app(Cart::class)->raw());
    }

    public function test_home_slider_keeps_its_pick_for_the_set_hours(): void
    {
        Product::factory()->count(30)->create(['cover_image' => 'https://img.test/a.jpg']);
        $slides = fn () => Livewire::test(Home::class)->viewData('slides')->modelKeys();

        $first = $slides();
        $this->travel(119)->minutes();
        $this->assertSame($first, $slides());

        Product::find($first[0])->update(['hidden' => true]); // drops out at once
        $this->assertSame(array_slice($first, 1), $slides());

        $this->travel(2)->minutes(); // past the 2 hours: 5 slides again
        $this->assertCount(5, $slides());

        Setting::set('slider_cache_hours', 0);
        $this->assertNotSame($slides(), $slides());
    }

    public function test_description_scripts_are_removed(): void
    {
        $product = Product::factory()->create(['description' => '<p>وصف</p><script>alert(1)</script><img src=x onerror="alert(2)">']);

        $this->get('/products/'.$product->id)->assertOk()->assertSee('<p>وصف</p>', false)
            ->assertDontSee('alert(1)', false)->assertDontSee('alert(2)', false);
    }
}
