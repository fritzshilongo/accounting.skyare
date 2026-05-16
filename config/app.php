<?php

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

return [
    'name' => env('APP_NAME', 'Skyare'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'asset_url' => env('ASSET_URL', null),
    'base_domain' => env('APP_BASE_DOMAIN', env('BASE_DOMAIN', 'skyare.space')),
    'default_subdomain' => env('APP_DEFAULT_SUBDOMAIN', env('DEFAULT_SUBDOMAIN', 'www')),
    'license_issuer_subdomain' => env('APP_LICENSE_ISSUER_SUBDOMAIN', env('LICENSE_ISSUER_SUBDOMAIN', 'license')),
    'reserved_subdomains' => array_filter(array_map('trim', explode(',', env('APP_RESERVED_SUBDOMAINS', env('RESERVED_SUBDOMAINS', 'webmail,cpanel,mail,ftp,whm,admin,autodiscover,smtp,imap,pop'))))),
    'timezone' => 'UTC',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'faker_locale' => 'en_US',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',

    'providers' => ServiceProvider::defaultProviders()->merge([
        Barryvdh\DomPDF\ServiceProvider::class,
        Laravel\Sanctum\SanctumServiceProvider::class,
        Laravel\Tinker\TinkerServiceProvider::class,
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
    ])->toArray(),

    'aliases' => Facade::defaultAliases()->merge([
        'Pdf' => Pdf::class,
    ])->toArray(),
];
