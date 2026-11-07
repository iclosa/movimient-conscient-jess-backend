<?php

namespace App\Providers;

use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        Paginator::defaultView('vendor.pagination.admin');

        // Les imatges pujades es serveixen amb el mateix host pel qual el mòbil crida l'API
        // (APP_URL pot ser un domini local que el telèfon no resol).
        if (! $this->app->runningInConsole() && request()->is('api/*')) {
            config(['filesystems.disks.public.url' => request()->getSchemeAndHttpHost().'/storage']);
        }

        // @money($cents) → "12,50 €"
        Blade::directive('money', fn (string $expression) => '<?php echo \\'.Money::class."::format({$expression}); ?>");
    }
}
