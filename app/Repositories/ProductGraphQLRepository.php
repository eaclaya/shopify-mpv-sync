<?php

namespace App\Repositories;

use App\Facades\ShopifyGraphQL;
use App\Models\Product;
use Carbon\Carbon;
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
        if (is_object($product)) {
            $product = (array) $product;
        }
        if (!isset($product['shopify_product_id']) || is_null($product['shopify_product_id'])) {
            $variantInfo = ShopifyGraphQL::getProductAndVariantBySku($product['product_key']);
            if (!$variantInfo) {
                Log::error('Producto no tiene ID de producto de Shopify: ', [$product]);
                throw new \Exception("Producto no tiene ID de producto de Shopify: {$product['product_key']}");
            }
            $parts = explode('/', $variantInfo['productId']);
            $product['shopify_product_id'] = end($parts);
        }

        try {
            $title = $product['notes'];
            $imageUrl = isset($product['picture']) ? $product['picture'] : null;
            // $price = $product['price'];
            $productGlobalId = $product['shopify_product_id'];
            $productGlobalId = ShopifyGraphQL::toGlobalId('Product', $productGlobalId);

            $updateProductResponse = ShopifyGraphQL::updateProductTitleAndBody(
                $productGlobalId,
                $title
            );

            if (!isset($imageUrl) || trim($imageUrl) === '') {
                $updateImageResponse = 'No se proporcionó una Imagen del Producto.';
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
            }

            $variantId = $variantInfo['variantId'];
            $locationNumericId = ShopifyGraphQL::getLocationGlobalId($this->locationId);

            $updateInventoryResponse = ShopifyGraphQL::updateInventoryByVariant(
                $variantId,
                $locationNumericId,
                $product['qty']
            );

            // $updatePriceResponse = ShopifyGraphQL::updatePriceByVariant(
            //     $variantId,
            //     $price,
            // );

            Log::info('Producto actualizado correctamente:', [
                'product_key' => $product['product_key'],
                'response' => [
                    'product' => $updateProductResponse,
                    'image' => $updateImageResponse,
                    'inventory' => $updateInventoryResponse,
                    // 'price' => $updatePriceResponse
                ]
            ]);

            return [
                'product' => $updateProductResponse,
                'inventory' => $updateInventoryResponse,
            ];
        } catch (\Exception $e) {
            Log::error('Error al actualizar producto: ', [
                'product_key' => $product['product_key'],
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Error al actualizar producto: {$product['product_key']}: {$e->getMessage()}");
        }
    }

    public function create($product)
    {
        // if (!isset($product['picture'])) {
        //     Log::error('Producto no tiene imagen: ', [$product]);
        //     throw new \Exception("Producto no tiene imagen: {$product['product_key']}");
        // }

        if (is_object($product)) {
            $product = (array) $product;
        }

        try {
            $variantInfo = ShopifyGraphQL::getProductAndVariantBySku($product['product_key']);

            if (!$variantInfo) {
                $createProductResponse = ShopifyGraphQL::createProductWithVariant(
                    $product
                );
                Log::info('respuesta al momento de crear el Producto: ', [$createProductResponse]);
                if (!empty($createProductResponse['data']['productCreate']['product'])) {
                    $createProduct = $createProductResponse['data']['productCreate']['product'];
                    $parts = explode('/', $createProduct['id']);
                    $productId = end($parts);
                    $product['shopify_product_id'] = $productId;
                    $product['id'] = $productId;

                    $publications = ShopifyGraphQL::getPublications();
                    $edgesPublications = $publications['data']['publications']['edges'];
                    $arrayPublications = [];
                    foreach ($edgesPublications as $edge) {
                        $arrayPublications[] = [
                            'publicationId' => $edge['node']['id'],
                            'publishDate' => Carbon::now('UTC')->format('Y-m-d\TH:i:s\Z'),
                        ];
                    }
                    ShopifyGraphQL::setPublicationsInToProduct($productId, $arrayPublications);
                } else {
                    throw new \Exception("No se creo la variante para el SKU: {$product['product_key']}");
                }
            } else {
                $parts = explode('/', $variantInfo['productId']);
                $productId = end($parts);
                $product['shopify_product_id'] = $productId;
                $product['id'] = $productId;
            }

            Log::error('Producto creado exitosamente: ', [$product]);
            return $product;
        } catch (\Exception $e) {
            Log::error('Error al crear producto:', [
                'product_key' => $product['product_key'],
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Error al crear producto: {$product['product_key']}");
        }
    }
}
