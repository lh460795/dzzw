<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Traits\District;
class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    use District;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
