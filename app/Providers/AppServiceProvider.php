<?php

namespace App\Providers;

use App\Repositories\ChatbotRepository;
use App\Services\ShopifyService;
use App\Services\ChatbotService;
use App\Services\WhatsappService;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Shopify', function () {
            return new ShopifyService(
                $this->app->make(ShopifyService::class)
            );
        });
        $this->app->singleton('Chatbot', function () {
            return new ChatbotService();
        });
        $this->app->singleton('Whatsapp', function () {
            return new WhatsappService();
        });
        $this->app->singleton(ChatbotRepository::class, function () {
            return new ChatbotRepository();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
    }
}
