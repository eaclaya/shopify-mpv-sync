<?php

namespace App\Repositories;

use App\Facades\ShopifyGraphQL;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductGraphQLRepository
{
    protected $locationId;
    public function __construct()
    {
        $this->locationId = config('services.shopify.location_id'); // debes tenerlo en config
    }

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
        if (!isset($product['picture']) || !isset($product['shopify_product_id'])) {
            return;
        }

        Log::info('Se Procede a actualizar el Siguiente Producto:', [$product['product_key']]);

        try {
            // Paso 1: Actualizar título, body, imagen
            $title = $product['notes'];
            $body = '<p>' . e($product['notes']) . '</p>';
            $imageUrl = $product['picture'];
            $productGlobalId = $product['shopify_product_id'];
            $productGlobalId = ShopifyGraphQL::toGlobalId('Product', $productGlobalId);

            $updateProductResponse = ShopifyGraphQL::updateProductTitleAndBodyAndImage(
                $productGlobalId,
                $title,
                $body,
            );

            $updateImageResponse = ShopifyGraphQL::replaceProductImage(
                $productGlobalId,
                $imageUrl
            );

            $variantInfo = ShopifyGraphQL::getProductAndVariantBySku($product['product_key']);

            if (!$variantInfo) {
                throw new \Exception("No se encontró la variante para el SKU: {$product['product_key']}");
            }

            $variantId = $variantInfo['variantId'];
            $locationNumericId = ShopifyGraphQL::getLocationGlobalId($this->locationId);

            Log::info('tengo la siguiente variante: ', [$variantId]);

            $updateInventoryResponse = ShopifyGraphQL::updateInventoryByVariant(
                $variantId,
                $locationNumericId,
                $product['qty']
            );

            Log::info('Producto actualizado correctamente:', [
                'product_key' => $product['product_key'],
                'response' => [
                    'product' => $updateProductResponse,
                    'image' => $updateImageResponse,
                    'inventory' => $updateInventoryResponse,
                ]
            ]);

            return [
                'product' => $updateProductResponse,
                'inventory' => $updateInventoryResponse,
            ];
        } catch (\Exception $e) {
            Log::error('Error al actualizar producto:', [
                'product_key' => $product['product_key'],
                'error' => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }
}
