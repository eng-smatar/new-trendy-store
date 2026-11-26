<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\ArabicText;
use App\Services\Mart\MartClient;
use App\Services\Pricing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SyncCatalog extends Command
{
    protected $signature = 'mart:sync-catalog {--quick : Stock and prices only, without categories or descriptions}';

    protected $description = 'Full sync of Mart categories and products';

    public function handle(MartClient $mart): int
    {
        ini_set('memory_limit', '256M'); // 1,000 products with descriptions per page

        // One catalogue pass at a time, whether quick, full or started from the admin.
        $lock = Cache::lock('mart-sync-catalog', 600);

        if (! $lock->get()) {
            $this->info('Another catalogue sync is running.');

            return self::SUCCESS;
        }

        try {
            $count = $this->sync($mart, (bool) $this->option('quick'));
        } catch (Throwable $e) {
            Setting::set('sync_last_error', ['at' => now()->toIso8601String(), 'message' => $e->getMessage()]);
            report($e);
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            $lock->release();
        }

        Setting::set('sync_last_success', now()->toIso8601String());
        $this->info("Synced $count products.");

        return self::SUCCESS;
    }

    private function sync(MartClient $mart, bool $quick): int
    {
        if (! $quick) {
            $this->syncCategories($mart);
        }

        $overrides = Product::whereNotNull('price_override')->pluck('price_override', 'id');
        $markup = (float) Setting::get('markup_percent');
        $now = now();
        $seen = [];
        $page = 1;

        do {
            $response = $mart->getPatiently('products', ['page' => $page++, 'limit' => 1000] + ($quick ? [] : ['include' => 'description']));

            $rows = array_map(function (array $p) use ($overrides, $markup, $now) {
                $cost = (float) $p['price']['group_price'];
                $override = isset($overrides[$p['product_id']]) ? (float) $overrides[$p['product_id']] : null;

                return [
                    'id' => $p['product_id'],
                    'reference' => $p['reference'] ?? null,
                    'name' => $p['name'],
                    'description' => $p['description'] ?? null,
                    'description_short' => $p['description_short'] ?? null,
                    'category_id' => $p['category']['category_id'] ?? null,
                    'brand_name' => $p['brand']['name'] ?? null,
                    'cost_price' => $cost,
                    'selling_price' => Pricing::selling($cost, $override, $markup),
                    'images' => json_encode($p['images'] ?? []),
                    'cover_image' => $p['cover_image'] ?? null,
                    'combinations' => json_encode($p['combinations'] ?? [], JSON_UNESCAPED_UNICODE),
                    'total_quantity' => ($p['in_stock'] ?? false) ? (int) $p['total_quantity'] : 0,
                    'search_text' => ArabicText::normalize(implode(' ', [
                        $p['name'], $p['reference'] ?? '', $p['brand']['name'] ?? '', $p['category']['category_name'] ?? '',
                    ])),
                    'mart_active' => ($p['status'] ?? null) === 'active',
                    'removed_at' => null,
                    'synced_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }, $response['data']['products']);

            // price_override, hidden, featured and created_at are never overwritten;
            // a quick pass has no descriptions, so it leaves those alone too.
            if ($rows) {
                $keep = ['id', 'created_at', ...($quick ? ['description', 'description_short'] : [])];
                Product::upsert($rows, ['id'], array_values(array_diff(array_keys($rows[0]), $keep)));
            }
            array_push($seen, ...array_column($rows, 'id'));
        } while ($response['meta']['pagination']['has_next'] ?? false);

        // Only reached when every page succeeded. An empty answer removes nothing.
        if ($seen) {
            Product::whereNull('removed_at')->whereNotIn('id', $seen)->update(['removed_at' => $now]);
        }

        // A price override or markup saved while pages were loading must win.
        Pricing::recalculate();

        return count($seen);
    }

    private function syncCategories(MartClient $mart): void
    {
        $categories = collect($mart->getPatiently('categories')['data']['categories'])->map(function (array $c) {
            $parts = array_map('trim', explode('>', $c['category_name']));

            return [
                'id' => $c['category_id'],
                'name' => implode(' > ', $parts),
                'parent_path' => count($parts) > 1 ? implode(' > ', array_slice($parts, 0, -1)) : null,
                'gender' => $c['gender'] ?? null,
            ];
        });
        $categories->chunk(500)->each(fn ($chunk) => Category::upsert($chunk->values()->all(), ['id']));
    }
}
