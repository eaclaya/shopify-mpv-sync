<?php

namespace App\Http\Controllers;

use App\Jobs\SentApiShopify;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    protected $productRepository;
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function store(Request $request)
    {
        $data = $request->all();

        $result = [];
        if (is_array($data)) {
            $product = $data;
            if ($product['product_key'] && $product['notes'] && $product['price'] && isset($product['qty'])) {
                $result = $this->productRepository->update($product);
            }

        }
        return response()->json($result);
    }

    public function update(Request $request)
    {
        $data = $request->all();

        $result = [];
        if (is_array($data)) {
            $products = $data['products'];
            $level = $data['level'];
            $count = 0;
            foreach ($products as $product) {
                if ($product['product_key'] && $product['notes'] && $product['price'] && isset($product['qty'])) {
                    dispatch((new SentApiShopify($product, $this->productRepository))->delay(10 * $count * $level));
                    $count++;
                }
            }
        }
        return response()->json($result);
    }
}
