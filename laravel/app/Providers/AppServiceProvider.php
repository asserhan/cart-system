<?php

namespace App\Providers;

use App\Domain\Cart\Repositories\CartRepositoryInterface;
use App\Domain\Cart\Services\CartReminderService;
use App\Infrastructure\Persistence\Eloquent\EloquentCartRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CartRepositoryInterface::class, EloquentCartRepository::class);

        $this->app->singleton(CartReminderService::class, function ($app) {
            return new CartReminderService(
                $app['config']->get('cart.reminders.intervals', [])
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
