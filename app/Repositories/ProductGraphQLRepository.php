<?php

namespace App\Repositories;

use App\Facades\ShopifyGraphQL;
use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductGraphQLRepository
{
    protected $locationId;
    public function __construct()
    {
        $this->locationId = config('services.shopify.location_id');
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
        Log::info('Tipo de $product:', [gettype($product)]);
        Log::info('Contenido de $product:', [$product]);
        if (is_object($product)) {
            $product = (array) $product;
            Log::info('se convieerte en array');
        }
        if (!isset($product['shopify_product_id'])) {
            Log::error('Producto no tiene ID de producto de Shopify:', [$product]);
            return;
        }

        Log::info('Se Procede a actualizar el Siguiente Producto:', [$product['product_key']]);

        try {
            $title = $product['notes'];
            $imageUrl = $product['picture'];
            $price = $product['price'];
            $productGlobalId = $product['shopify_product_id'];
            $productGlobalId = ShopifyGraphQL::toGlobalId('Product', $productGlobalId);

            $updateProductResponse = ShopifyGraphQL::updateProductTitleAndBody(
                $productGlobalId,
                $title
            );

            if (!isset($imageUrl) || trim($imageUrl) === '') {
                $updateImageResponse = 'No se puede actualizar la imagen del producto porque no se proporcionó una URL válida.';
            } else {
                $isOk = false;
                try {
                    $response = Http::get($imageUrl);
                    $isOk = $response->ok();
                } catch (Exception $e) {
                    $isOk = false;
                }

                if (!$isOk) {
                    $updateImageResponse = 'No se puede actualizar la imagen del producto porque la URL no es válida.';
                } else {
                    $updateImageResponse = ShopifyGraphQL::replaceProductImage(
                        $productGlobalId,
                        $imageUrl,
                        $title,
                    );
                }
            }

            $variantInfo = ShopifyGraphQL::getProductAndVariantBySku($product['product_key']);

            if (!$variantInfo) {
                throw new \Exception("No se encontró la variante para el SKU: {$product['product_key']}");
                return;
            }

            $variantId = $variantInfo['variantId'];
            $locationNumericId = ShopifyGraphQL::getLocationGlobalId($this->locationId);

            Log::info('tengo la siguiente variante: ', [$variantId]);

            $updateInventoryResponse = ShopifyGraphQL::updateInventoryByVariant(
                $variantId,
                $locationNumericId,
                $product['qty']
            );

            $updatePriceResponse = ShopifyGraphQL::updatePriceByVariant(
                $variantId,
                $price,
            );

            Log::info('Producto actualizado correctamente:', [
                'product_key' => $product['product_key'],
                'response' => [
                    'product' => $updateProductResponse,
                    'image' => $updateImageResponse,
                    'inventory' => $updateInventoryResponse,
                    'price' => $updatePriceResponse
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

    public function create($product)
    {
        if (!isset($product['picture'])) {
            Log::error('Producto no tiene imagen:', [$product]);
            return;
        }

        try {
            $createProductResponse = ShopifyGraphQL::createProductWithVariant(
                $product
            );
            if (!empty($createProductResponse['data']['productCreate']['product'])) {
                $createProduct = $createProductResponse['data']['productCreate']['product'];
                $parts = explode('/', $createProduct['id']);
                $productId = end($parts);
                $product['shopify_product_id'] = $productId;
                $product['id'] = $productId;
            } else {
                throw new \Exception("No se encontró la variante para el SKU: {$product['product_key']}");
            }

            return $product;
        } catch (\Exception $e) {
            Log::error('Error al actualizar producto:', [
                'product_key' => $product['product_key'],
                'error' => $e->getMessage(),
            ]);
            return ['error' => $e->getMessage()];
        }
    }
}
