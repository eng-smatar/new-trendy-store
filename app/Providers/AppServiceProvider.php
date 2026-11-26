<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
use App\Notifications\ResetPassword;
use Filament\Auth\Notifications\ResetPassword as FilamentResetPassword;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Filament builds its reset email from the container: ours signs the link differently.
        $this->app->bind(FilamentResetPassword::class, ResetPassword::class);

        // Scoped: read once per request or queued job.
        $this->app->scoped('store.settings', fn () => Setting::all()->pluck('value', 'key'));
        $this->app->scoped('store.listed_category_ids', fn () => ['ids' => Category::chosenBranchIds()]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceHttps(config('app.force_https'));
    }
}
