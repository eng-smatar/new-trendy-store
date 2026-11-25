<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Pricing;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;

class ManageSettings extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.manage-settings';

    public const KEYS = [
        'store_name', 'logo', 'icon', 'main_color',
        'footer_text', 'contact_phone', 'whatsapp', 'contact_email', 'contact_address', 'social_links',
        'announcement',
        'page_about', 'page_delivery', 'page_privacy', 'page_terms',
        'seo_title', 'seo_description', 'seo_image',
        'listing_min_profit', 'listing_categories',
        'ordering_enabled', 'closed_message', 'min_order_amount', 'markup_percent',
        'free_from', 'delivery_estimate',
    ];

    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('admin.settings');
    }

    public function getTitle(): string
    {
        return __('admin.settings');
    }

    public function mount(): void
    {
        $this->form->fill(collect(self::KEYS)->mapWithKeys(fn ($key) => [$key => Setting::get($key)])->all());
    }

    public function form(Schema $schema): Schema
    {
        $text = fn (string $name, int $max = 255) => TextInput::make($name)->label(__("admin.fields.$name"))->maxLength($max);
        $url = fn (string $name) => TextInput::make($name)->url()->rule('url:http,https')->maxLength(500);
        // Raster only: SVG can carry script.
        $image = fn (string $name) => FileUpload::make($name)->label(__("admin.fields.$name"))->disk('public')->directory('store')->visibility('public')
            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'])->maxSize(2048);
        $money = fn (string $name, string $label) => TextInput::make($name)->label($label)->numeric()->required()->minValue(0)->maxValue(1000000);

        return $schema->statePath('data')->components([
            Tabs::make()->persistTabInQueryString()->tabs([
                Tab::make(__('admin.tabs.ordering'))->schema([
                    Toggle::make('ordering_enabled')->label(__('admin.fields.ordering_enabled')),
                    $text('closed_message', 300)->required(),
                    $money('min_order_amount', __('admin.fields.min_order_amount')),
                    $money('markup_percent', __('admin.fields.markup_percent'))->suffix('%')->maxValue(1000),
                ]),
                Tab::make(__('admin.tabs.listing'))->schema([
                    Text::make(fn () => __('admin.listed_now', [
                        'count' => number_format(Product::visible()->count()),
                        'total' => number_format(Product::sellable()->count()),
                    ])),
                    $money('listing_min_profit', __('admin.fields.listing_min_profit'))->prefix('₪')
                        ->helperText(__('admin.listing_min_profit_help')),
                    Select::make('listing_categories')->label(__('admin.fields.listing_categories'))
                        ->multiple()->searchable()->options(fn () => self::categoryOptions())
                        ->helperText(__('admin.listing_categories_help')),
                ]),
                Tab::make(__('admin.tabs.shipping'))->schema([
                    $money('free_from.default', __('admin.zones.default')),
                    $money('free_from.Jerusalem', __('admin.zones.Jerusalem')),
                    $money('free_from.Isreal', __('admin.zones.Isreal')),
                    $text('delivery_estimate', 200)->required(),
                ]),
                Tab::make(__('admin.tabs.identity'))->schema([
                    $text('store_name', 60)->required(),
                    $image('logo'),
                    $image('icon'),
                    ColorPicker::make('main_color')->label(__('admin.fields.main_color'))->required()
                        ->rule('regex:/^#[0-9a-fA-F]{6}$/')
                        ->rule(fn () => function (string $attribute, $value, $fail) {
                            if (! self::readableUnderWhite((string) $value)) {
                                $fail(__('admin.errors.color_too_light'));
                            }
                        }),
                ]),
                Tab::make(__('admin.tabs.contact'))->schema([
                    Textarea::make('footer_text')->label(__('admin.fields.footer_text'))->maxLength(300),
                    $text('contact_phone', 40)->tel(),
                    $text('whatsapp', 20)->regex('/^[0-9]{8,15}$/')->helperText(__('admin.whatsapp_help')),
                    $text('contact_email')->email(),
                    $text('contact_address'),
                    Repeater::make('social_links')->label(__('admin.fields.social_links'))->maxItems(8)->defaultItems(0)->schema([
                        TextInput::make('label')->label(__('admin.name'))->required()->maxLength(40),
                        $url('url')->label(__('admin.fields.url'))->required(),
                    ]),
                ]),
                Tab::make(__('admin.tabs.home'))->schema([
                    $text('announcement', 200),
                ]),
                Tab::make(__('admin.tabs.pages'))->schema(array_map(
                    fn (string $page) => RichEditor::make("page_$page")->label(__("admin.fields.page_$page"))
                        ->toolbarButtons(['bold', 'italic', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'undo', 'redo']),
                    ['about', 'delivery', 'privacy', 'terms'],
                )),
                Tab::make(__('admin.tabs.seo'))->schema([
                    $text('seo_title', 70),
                    Textarea::make('seo_description')->label(__('admin.fields.seo_description'))->maxLength(160),
                    $image('seo_image'),
                ]),
            ]),
        ]);
    }

    /** Full path of every category, in the panel's language. */
    private static function categoryOptions(): array
    {
        $all = Category::orderBy('name')->get()->keyBy('name');

        return $all->mapWithKeys(function (Category $category) use ($all) {
            if (app()->getLocale() !== 'ar') {
                return [$category->id => $category->name];
            }

            $parts = explode(' > ', $category->name);
            $labels = array_map(function (int $i) use ($parts, $all) {
                $path = implode(' > ', array_slice($parts, 0, $i + 1));

                return ($all->get($path) ?? new Category(['name' => $path]))->label();
            }, array_keys($parts));

            return [$category->id => implode(' › ', $labels)];
        })->all();
    }

    /** WCAG contrast of white text on this colour is at least 4.5:1. */
    public static function readableUnderWhite(string $hex): bool
    {
        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $hex)) {
            return false;
        }

        [$r, $g, $b] = array_map(function (string $pair) {
            $c = hexdec($pair) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($hex, 1), 2));

        return 1.05 / (0.2126 * $r + 0.7152 * $g + 0.0722 * $b + 0.05) >= 4.5;
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $oldMarkup = (float) Setting::get('markup_percent');

        foreach (self::KEYS as $key) {
            $value = $data[$key] ?? null;
            Setting::set($key, in_array($key, ['social_links', 'listing_categories'], true) ? array_values($value ?? []) : $value);
        }

        if ((float) Setting::get('markup_percent') !== $oldMarkup) {
            Pricing::recalculate();
        }

        Notification::make()->success()->title(__('admin.saved'))->send();
    }
}
