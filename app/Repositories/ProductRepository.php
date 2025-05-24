<?php

namespace App\Repositories;

use App\Facades\Shopify;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ProductRepository
{
    public function all()
    {
        $host = config('services.shopify.host');
        return  Product::sync()->get()->map(function ($product) use ($host) {
            return [
                'id' => $product->id,
                'notes' => $product->notes,
                'product_key' => $product->product_key,
                'qty' => $product->qty,
                'price' => $product->price,
                'picture' => $product->picture,
                'shopify_product_id' => $product->shopify_product_id,
                'shopify_product_url' => $product->shopify_product_id ? "https://{$host}.myshopify.com/admin/products/{$product->shopify_product_id}" : null,
                'shopify_sync' => $product->shopify_sync
            ];
        });
    }

    public function filter($filter)
    {
        return Product::sync()->where('notes', 'like', "%$filter%")->where('product_key', $filter)->get();
    }

    public function update($product)
    {
        if (!isset($product['picture'])) {
            return;
        }
        Log::info('Se Procede a actualizar el Siguiente Producto:', $product['product_key']);
        Log::info('log product', [$product]);
        $data = [
            'product' => [
                'title' => $product['notes'],
                'body' => $product['notes'],
                'vendor' => 'KM Motos',
                'status' => 'active',
                'published' => true,
                'variants' => [
                    [
                        "option1" => "Default Title",
                        "price" => $product['price'],
                        "sku" => $product['product_key'],
                        "inventory_quantity" => $product['qty']
                    ]
                ],
                'images' => [
                    [
                        "src" => $product['picture']
                    ]
                ]
            ]
        ];
        $response = [];
        if (isset($product['shopify_product_id'])) {
            $data['product']['id'] = $product['shopify_product_id'];
            $response = Shopify::put("products/{$product['shopify_product_id']}", $data);
        } else {
            $response = Shopify::post("products", $data);
        }
        Log::info('Se termina de actualizar el Siguiente Producto:', $product['product_key']);
        Log::info('Optuve el siguiente responce:', $response);
        if (isset($response['errors'])) {
            if (!is_array($response['errors'])) {
                Log::error('Unexpected error:', ['errors' => $response['errors']]);
            } else {
                $errors = json_encode($response['errors']);
                Log::error('Unexpected error:', ['errors' => $errors]);
            }
        }
        /* if(isset($response['id'])){
            Shopify::post('collects', [
                'collect' => [
                    'product_id' => $response['id'],
                    'collection_id' => config('services.shopify.collection_id')
                ]
            ]);
        } */
        return $response;
    }
}
