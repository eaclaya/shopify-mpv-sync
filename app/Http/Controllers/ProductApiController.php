<?php

namespace App\Http\Controllers;

use App\Jobs\SentApiShopify;
use App\Jobs\SentApiShopifyGraphQL;
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
            try {
                $product = $data;
                if ($product['product_key'] && $product['notes'] && $product['price'] && isset($product['qty'])) {
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
                    if ($product['product_key'] && $product['notes'] && $product['price'] && isset($product['qty'])) {
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
                $level = isset($data['level']) ? $data['level'] : 1;
                $count = 0;
                foreach ($products as $product) {
                    if ($product['product_key'] && $product['notes'] && $product['price']) {
                        dispatch((new SentApiShopifyGraphQL($product))->delay(20 * $count * $level));
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
