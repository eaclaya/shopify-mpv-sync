<?php

namespace App\Http\Controllers;

use App\Jobs\SentApiShopify;
use App\Jobs\SentApiShopifyGraphQL;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            try {
                $product = $data;
                if ($product['product_key'] && $product['notes'] && $product['price']) {
                    $result = $this->productRepository->update($product);
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error fetching products: ' . $e->getMessage()], 500);
            }
        }
        return response()->json($result);
    }

    public function update(Request $request)
    {
        $data = $request->all();

        try {
            if (is_array($data)) {
                $products = $data['products'];
                $level = isset($data['level']) ? $data['level'] : 1;
                $count = 0;
                foreach ($products as $product) {
                    if ($product['product_key'] && $product['notes'] && $product['price']) {
                        dispatch((new SentApiShopify($product))->delay(20 * $count * $level));
                        $count++;
                    }
                }
                return response()->json(['success' => 'Products have been updated successfully'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching products: ' . $e->getMessage()], 500);
        }
    }

    public function updateGraphQl(Request $request)
    {
        $data = $request->all();

        try {
            if (is_array($data)) {
                $products = $data['products'];
                $level = isset($data['level']) ? $data['level'] : 0;
                $count = 1;
                $time = 60;
                $rowsQty = 25;

                foreach ($products as $product) {
                    Log::info('Entro en el array con el producto: ', [$product]);
                    $delay = $time * $count + ($level * $rowsQty * $time);
                    if ($product['product_key'] && $product['notes'] && $product['price']) {
                        Log::info('pase el check y paso a procesar el producto en un queue: ', [$product]);
                        dispatch((new SentApiShopifyGraphQL($product))->delay($delay));
                        $count++;
                    }
                }
                return response()->json(['success' => 'Products have been updated successfully'], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching products: ' . $e->getMessage()], 500);
        }
    }
}
