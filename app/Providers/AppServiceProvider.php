<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Keepsuit\Liquid\EnvironmentFactory;
use App\Liquid\CustomTags\SchemaTag;

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
    public function boot()
    {
        \Liquid\Liquid::set('DEBUG', true);
    }
}
