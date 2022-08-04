<?php
namespace App\Repositories;

use App\Facades\Shopify;
use App\Models\Product;

class ProductRepository
{
    public function all()
    {
        $host = config('services.shopify.host');
        return  Product::sync()->get()->map(function($product) use ($host){
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

    public function filter($filter){
        return Product::sync()->where('notes', 'like', "%$filter%")->where('product_key', $filter)->get();
    }

    public function update(Product $product){
        $data = [
            'title' => $product->notes,
            'status' => 'active',
            'published' => true,
            'variants' => [
                            [
                                "option1" => "Default Title",
                                "price" => $product->price,
                                "sku" => $product->product_key
                            ]
                        ],
            'images' => [
                [
                    "src" => $product->picture
                    ]
                ]
        ];
        $response = Shopify::post("products", $data);
        if(isset($response['errors'])){
            throw new \Exception($response['errors']);
        }else{
            $product->shopify_product_id = $response['product']['id'];
            $product->save();
        }
        return $product;
    }
}