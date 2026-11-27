<?php

namespace App\Providers;

use App\Models\Account;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Stripe\StripeClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Cashier::useCustomerModel(Account::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Lets code resolve a configured client (and tests swap in a mock)
        $this->app->bind(StripeClient::class, fn() => new StripeClient(config('services.stripe.secret')));
    }
}
