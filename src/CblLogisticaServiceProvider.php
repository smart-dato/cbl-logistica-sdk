<?php

namespace SmartDato\CblLogistica;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use SmartDato\CblLogistica\Cache\LaravelTokenStore;
use SmartDato\CblLogistica\Contracts\TokenStore;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CblLogisticaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('cbl-logistica-sdk')
            ->hasConfigFile();
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(TokenStore::class, fn ($app): TokenStore => new LaravelTokenStore(
            $app->make(CacheFactory::class)->store(config('cbl-logistica-sdk.cache.store')),
        ));

        /*
         * The singleton deliberately holds no credentials — an application may serve
         * many CBL accounts, and each one is obtained with withCredentials().
         */
        $this->app->singleton(CblLogistica::class, fn ($app): CblLogistica => new CblLogistica(
            tokenStore: $app->make(TokenStore::class),
        ));
    }
}
