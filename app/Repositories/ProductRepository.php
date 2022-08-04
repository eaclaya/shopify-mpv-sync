<?php
namespace App\Repositories;

use App\Facades\Shopify;
use App\Models\Product;

class ProductRepository
{
    public function all()
    {
        return Product::sync()->get();
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