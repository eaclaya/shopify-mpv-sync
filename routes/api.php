<?php

use App\Http\Controllers\DaybookApiController;
use App\Http\Controllers\ProductApiController;
use App\Http\Controllers\WhatsappApiController;
use Illuminate\Http\Request;
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
    Route::post('/products', [ProductApiController::class, 'store']);
});
Route::get('/whatsapp/webhook', [WhatsappApiController::class, 'hook']);
Route::post('/whatsapp/webhook', [WhatsappApiController::class, 'hook']);

Route::post('/daybook', [DaybookApiController::class, 'hook']);
