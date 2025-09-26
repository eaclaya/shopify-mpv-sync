<?php

namespace App\Http\Controllers;

use App\Facades\ShopifyGraphQL;
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
                Log::info('E recibido el siguiente producto: ', $product);
                if ($product['product_key'] && $product['notes'] && $product['price']) {
                    Log::info('pase la prueba con el producto: ', $product);
                    if (!isset($product['shopify_product_id'])) {
                        Log::info('no tenia shopify_product_id y prosigo a crearlo: ', $product);
                        $result = $this->productRepository->create($product);
                        Log::info('cree el shopify_product_id: ', $result);
                    } else {
                        Log::info('si tenia el shopify_product_id: ', $result);
                        $result = $product;
                    }
                    if (isset($result['shopify_product_id'])) {
                        Log::info('paso a actualizar el producto: ', $result);
                        dispatch((new SentApiShopifyGraphQL($result))->delay(60));
                    }
                    $result['id'] = $result['shopify_product_id'];
                }
            } catch (\Exception $e) {
                Log::error("ShopifyApi Service Error: " . $e->getMessage());
                return response()->json(['error' => 'Error fetching products: ' . $e->getMessage()], 500);
            }
        } else {
            Log::error("ShopifyApi Service Error: Data no es Array");
            return response()->json(['error' => 'Error fetching products: Data no es Array'], 500);
        }
        Log::info("retorno el resultado: ", $result);
        return response()->json(['product' => $result], 200);
    }

    public function update(Request $request)
    {
        $data = $request->all();
        try {
            if (is_array($data)) {
                $products = isset($data['products']) ? $data['products'] : null;
                $level = isset($data['level']) ? $data['level'] : 1;
                $count = 0;
                $time = 15;
                if (!isset($products)) {
                    return response()->json(['success' => 'No products found'], 200);
                }
                foreach ($products as $product) {
                    $delay = $level * ($count * $time);
                    if ($product['product_key'] && $product['notes'] && $product['price']) {
                        Log::info('pase el check y paso a procesar el producto en un queue: ', [$product]);
                        dispatch((new SentApiShopifyGraphQL($product))->delay($delay));
                        $count++;
                    } else {
                        Log::error('no pase el check, por lo tanto no paso a un queue: ', [$product]);
                    }
                }
                return response()->json(['success' => 'Products have been updated successfully'], 200);
            }
            return response()->json(['error' => 'Error fetching products'], 500);
        } catch (\Exception $e) {
            Log::error('Hubo un error en el controlador: ', [$e->getMessage()]);
            return response()->json(['error' => 'Error fetching products: ' . $e->getMessage()], 500);
        }
    }

    public function getOrders(Request $request)
    {
        $data = $request->all();
        $orderNumber = isset($data['order_number']) ? $data['order_number'] : null;
        if (!isset($orderNumber)) {
            return response()->json(['error' => 'No se recibio un numero de orden'], 500);
        }
        Log::info('E recibido el siguiente numero de orden: ', [ $orderNumber ]);
        $orders = ShopifyGraphQL::getOrdersByNumber($orderNumber);
        $edgesOrders = $orders['data']['orders']['edges'][0]['node'];
        $lineItems = $edgesOrders['lineItems']['edges'];
        $products = [];
        foreach ($lineItems as $lineItem) {
            $products[] = [
                'product_key' => $lineItem['node']['variant']['sku'],
                'qty' => $lineItem['node']['quantity'],
                'price' => $lineItem['node']['variant']['price'],
            ];
        }
        $result[] = [
            'client' => $edgesOrders['customer'],
            'address' => $edgesOrders['shippingAddress'],
            'products' => $products,
        ];
        Log::info('orders: ', [ $result ]);
        return response()->json(['orders' => $result], 200);
    }
}
