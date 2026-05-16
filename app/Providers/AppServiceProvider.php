<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Core\Database::class, function ($app) {
            return new \App\Core\Database();
        });

        $this->app->singleton(\App\Core\RequestContext::class, function ($app) {
            $appConfig = $app['config']['app'] ?? [];
            return new \App\Core\RequestContext($appConfig);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ((string) config('database.default', '') === '') {
            config(['database.default' => 'mysql']);
        }

        $shouldForceHttps = (bool) env('FORCE_HTTPS', false)
            || app()->environment('production');
        if ($shouldForceHttps) {
            URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);
    }
}
