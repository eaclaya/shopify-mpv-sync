<?php

use App\Http\Controllers\ShopifyApiController;
use App\Http\Controllers\SupabaseApiController;
// use App\Http\Controllers\WhatsappApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::post('/shopify/products', [ShopifyApiController::class, 'store']);
    Route::post('/shopify/products/update', [ShopifyApiController::class, 'update']);

    Route::post('/supabase/', [SupabaseApiController::class, 'store']);
    Route::post('/supabase/update', [SupabaseApiController::class, 'update']);
});

// Route::get('/whatsapp/webhook', [WhatsappApiController::class, 'hook']);
// Route::post('/whatsapp/webhook', [WhatsappApiController::class, 'hook']);
