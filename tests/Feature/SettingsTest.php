<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Town;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $locale = 'ar'): User
    {
        $user = User::factory()->create(['locale' => $locale]);
        $this->actingAs($user);

        return $user;
    }

    public function test_saved_settings_show_publicly_and_markup_reprices(): void
    {
        $this->admin();
        $product = Product::factory()->create();

        Livewire::test(ManageSettings::class)
            ->set('data.store_name', 'متجري')->set('data.contact_phone', '0599111222')->set('data.markup_percent', 50)
            ->call('save')->assertHasNoFormErrors();

        $this->get('/')->assertOk()->assertSee('متجري')->assertSee('0599111222');
        $this->assertEquals(150, $product->fresh()->selling_price);
    }

    public function test_invalid_input_keeps_the_old_value(): void
    {
        Storage::fake('public');
        $this->admin();
        Setting::set('store_name', 'قديم');

        Livewire::test(ManageSettings::class)
            ->set('data.store_name', 'جديد')->set('data.main_color', '#ffff00')->set('data.contact_email', 'nope')
            ->set('data.logo', [UploadedFile::fake()->create('x.svg', 10, 'image/svg+xml')])
            ->call('save')->assertHasFormErrors(['main_color', 'contact_email', 'logo']);

        $this->assertSame('قديم', Setting::get('store_name'));
    }

    public function test_footer_shows_real_delivery_fees_and_socials(): void
    {
        foreach ([[1, 248, 20], [2, 248, 30], [3, 256, 65]] as [$id, $district, $fee]) {
            Town::create(['id' => $id, 'district_id' => $district, 'district_name' => 'd', 'name' => 't', 'available' => true,
                'fees' => [['from_total' => 0, 'to_total' => null, 'fee' => $fee]]]);
        }

        $this->get('/')->assertOk()
            ->assertSee(__('store.fee_range', ['min' => '20', 'max' => '30']))->assertSee('₪65')
            ->assertSee(__('store.free_from', ['amount' => '1,000']))
            ->assertDontSee(__('store.zones.default').':')
            ->assertSee('instagram.com/trendy.windy.79', false)->assertSee('aria-label="TikTok"', false);
    }

    public function test_listing_filters_by_profit_and_category(): void
    {
        $this->admin();
        Category::create(['id' => 1, 'name' => 'home']);
        Category::create(['id' => 2, 'name' => 'home > lamps', 'parent_path' => 'home']);
        Category::create(['id' => 3, 'name' => 'electronics']);
        $big = Product::factory()->create(['category_id' => 2]);                     // profit 20
        $small = Product::factory()->create(['category_id' => 2, 'cost_price' => 50]); // profit 10
        $other = Product::factory()->create(['category_id' => 3]);
        $none = Product::factory()->create(['category_id' => null]);
        $listed = fn () => Product::visible()->orderBy('id')->pluck('id')->all();
        $ids = fn (Product ...$products) => collect($products)->pluck('id')->sort()->values()->all();

        $this->assertSame($ids($big, $small, $other, $none), $listed());

        Livewire::test(ManageSettings::class)
            ->set('data.listing_min_profit', 20)->set('data.listing_categories', [1])
            ->call('save')->assertHasNoFormErrors();

        $this->assertSame($ids($big), $listed()); // parent category covers its child; exactly 20 passes
        $this->get('/products/'.$big->id)->assertOk();
        $this->get('/products/'.$small->id)->assertNotFound();
        $this->get('/products/'.$other->id)->assertNotFound();
        $this->assertSame([1], Category::roots()->pluck('id')->all());
        $this->assertTrue($small->isSellable()); // so an order already placed can still be approved
    }

    public function test_unknown_category_and_negative_profit_are_rejected(): void
    {
        $this->admin();

        Livewire::test(ManageSettings::class)
            ->set('data.listing_min_profit', -1)->set('data.listing_categories', [999])
            ->call('save')->assertHasFormErrors(['listing_min_profit', 'listing_categories.0']);
    }

    public function test_page_html_is_sanitised(): void
    {
        Setting::set('page_about', '<p>مرحبا</p><script>alert(1)</script>');

        $this->get('/pages/about')->assertOk()->assertSee('مرحبا')->assertDontSee('alert(1)', false);
    }

    public function test_admin_language_follows_the_user(): void
    {
        $this->admin('en');
        $this->get('/admin/orders')->assertSee('Orders')->assertSee('dir="ltr"', false);

        $this->admin('ar');
        $this->get('/admin/orders')->assertSee('الطلبات')->assertSee('dir="rtl"', false);
    }

    public function test_admin_translations_match(): void
    {
        $keys = fn (string $locale) => array_keys(Arr::dot(require lang_path("$locale/admin.php")));

        $this->assertSame($keys('ar'), $keys('en'));
    }
}
