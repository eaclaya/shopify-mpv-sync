<?php

namespace App\Http\Controllers;

use App\Jobs\SentApiShopifyGraphQL;
use App\Repositories\ProductGraphQLRepository;
// use App\Repositories\ProductRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShopifyApiController extends Controller
{
    protected $productRepository;
    public function __construct(ProductGraphQLRepository $productRepository)
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
                    if (!isset($product['shopify_product_id'])) {
                        $result = $this->productRepository->create($product);
                    } else {
                        $result = $product;
                    }
                    if (isset($result['shopify_product_id'])) {
                        dispatch((new SentApiShopifyGraphQL($result))->delay(15));
                    }
                }
            } catch (\Exception $e) {
                return response()->json(['error' => 'Error fetching products: ' . $e->getMessage()], 500);
            }
        } else {
            return response()->json(['error' => 'Error fetching products: Data no es Array'], 500);
        }
        return response()->json($result);
    }

    public function update(Request $request)
    {
        $data = $request->all();

        try {
            if (is_array($data)) {
                $products = $data['products'];
                $level = isset($data['level']) ? $data['level'] : 0;
                $count = 1;
                $time = 90;
                $rowsQty = 25;

                foreach ($products as $product) {
                    $delay = $time * $count + ($level * $rowsQty * $time);
                    if ($product['product_key'] && $product['notes'] && $product['price']) {
                        Log::info('pase el check y paso a procesar el producto en un queue: ', [$product]);
                        dispatch((new SentApiShopifyGraphQL($product))->delay($delay));
                        $count++;
                    }
                }
                return response()->json(['success' => 'Products have been updated successfully'], 200);
            }
            return response()->json(['error' => 'Error fetching products'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching products: ' . $e->getMessage()], 500);
        }
    }

}
