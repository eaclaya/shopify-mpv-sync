<?php

namespace App\Providers;

// use App\Repositories\ChatbotRepository;
// use App\Services\ChatbotService;
// use App\Services\WhatsappService;
use App\Services\ShopifyService;
use App\Services\ShopifyGraphQLService;
use App\Services\SupabaseService;
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
        //ShopifyGraphQL
        $this->app->singleton('ShopifyGraphQL', function () {
            return new ShopifyGraphQLService(
                $this->app->make(ShopifyGraphQLService::class)
            );
        });
        //Supabase
        $this->app->singleton('Supabase', function () {
            return new SupabaseService(
                $this->app->make(SupabaseService::class)
            );
        });
        // $this->app->singleton('Chatbot', function () {
        //     return new ChatbotService();
        // });
        // $this->app->singleton('Whatsapp', function () {
        //     return new WhatsappService();
        // });
        // $this->app->singleton(ChatbotRepository::class, function () {
        //     return new ChatbotRepository();
        // });
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
