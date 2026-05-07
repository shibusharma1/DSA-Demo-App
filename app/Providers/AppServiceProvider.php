<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Observers\CustomerObserver;
use App\Observers\PaymentObserver;
use App\Observers\ProductObserver;
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
        Customer::observe(CustomerObserver::class);
        Product::observe(ProductObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}
