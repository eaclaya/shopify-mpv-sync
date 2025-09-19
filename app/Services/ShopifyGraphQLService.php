<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyGraphQLService
{
    public function query($query, $variables = [])
    {
        return $this->makeGraphQLRequest($query, $variables);
    }

    public function mutation($mutation, $variables = [])
    {
        return $this->makeGraphQLRequest($mutation, $variables);
    }

    protected function makeGraphQLRequest($queryOrMutation, $variables = [])
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->getEndpoint(), [
                    'query' => $queryOrMutation,
                    'variables' => $variables,
                ]);

            $data = $response->json();

            if (isset($data['errors'])) {
                return ['errors' => $data['errors']];
            }

            return $data;
        } catch (\Exception $e) {
            return ['errors' => [['message' => $e->getMessage()]]];
        }
    }

    protected function getEndpoint()
    {
        $host = config('services.shopify.host');
        $version = config('services.shopify.version');
        return "https://{$host}.myshopify.com/admin/api/{$version}/graphql.json";
    }

    protected function getHeaders()
    {
        return [
            'X-Shopify-Access-Token' => config('services.shopify.access_token'),
            'Content-Type' => 'application/json',
        ];
    }

    public function toGlobalId(string $resourceName, int $id): string
    {
        return "gid://shopify/{$resourceName}/{$id}";
    }

    public function getProductVariantGlobalId(int $productVariantNumericId): string
    {
        return $this->toGlobalId('ProductVariant', $productVariantNumericId);
    }

    public function getLocationGlobalId(int $locationNumericId): string
    {
        return $this->toGlobalId('Location', $locationNumericId);
    }

    public function getProductAndVariantBySku(string $sku): ?array
    {
        $query = '
            query getProductBySku($sku: String!) {
                productVariants(first: 10, query: $sku) {
                    edges {
                        node {
                            id
                            sku
                            product {
                                id
                                title
                            }
                        }
                    }
                }
            }
        ';

        $response = $this->query($query, ['sku' => $sku]);

        Log::info("En getProductAndVariantBySku del producto, {$sku}, tengo el siguiente variant de respuesta: ", [ $response ]);

        $variantEdge = $response['data']['productVariants']['edges'][0]['node'] ?? null;

        return $variantEdge ? [
            'variantId' => $variantEdge['id'],
            'productId' => $variantEdge['product']['id'],
            'sku' => $variantEdge['sku'],
        ] : null;
    }

    public function updateInventoryByVariant(string $productVariantGlobalId, string $locationGlobalId, int $newQuantity): array
    {
        $queryGetInventoryItem = '
            query GetInventoryItemByVariant($id: ID!) {
                node(id: $id) {
                    ... on ProductVariant {
                        inventoryItem {
                            id
                        }
                    }
                }
            }
        ';
        $variablesGetInventoryItem = [
            'id' => $productVariantGlobalId,
        ];

        $inventoryItemResponse = $this->query($queryGetInventoryItem, $variablesGetInventoryItem);

        if (isset($inventoryItemResponse['data']['node']['inventoryItem']['id'])) {
            $inventoryItemId = $inventoryItemResponse['data']['node']['inventoryItem']['id'];
        } else {
            throw new \Exception("No se pudo obtener el inventoryItem ID para la variante: {$productVariantGlobalId}");
        }

        $mutation = '
            mutation inventorySetOnHandQuantities($input: InventorySetOnHandQuantitiesInput!) {
                inventorySetOnHandQuantities(input: $input) {
                    userErrors {
                        field
                        message
                    }
                    inventoryAdjustmentGroup {
                        createdAt
                        reason
                        changes {
                            name
                            delta
                        }
                    }
                }
            }
        ';

        $dataSerQuantities = [
            'inventoryItemId' => $inventoryItemId,
            'locationId' => $locationGlobalId,
            'quantity' => $newQuantity,
        ];

        Log::info('Se Procede a actualizar la cantidad de productos en el inventario para la variante:', [
            $dataSerQuantities
        ]);

        $variables = [
            'input' => [
                'reason' => 'correction',
                'setQuantities' => [
                    $dataSerQuantities
                ]
            ]
        ];

        return $this->query($mutation, $variables);
    }

    public function updatePriceByVariant(string $productVariantGlobalId, int $newPrice): array
    {
        $mutation = '
            mutation productVariantUpdate($input: ProductVariantInput!) {
                productVariantUpdate(input: $input) {
                    productVariant {
                        id
                        price
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'id' => $productVariantGlobalId,
                'price' => number_format($newPrice, 2, '.', ''),
            ]
        ];

        return $this->query($mutation, $variables);
    }

    public function updateProductTitleAndBody(string $productId, string $title): array
    {
        $mutation = '
            mutation productUpdate($input: ProductInput!) {
                productUpdate(input: $input) {
                    product {
                        id
                        title
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'id' => $productId,
                'title' => $title,
            ]
        ];

        return $this->mutation($mutation, $variables);
    }

    public function replaceProductImage(string $productId, string $newImageUrl, string $title): array
    {
        $query = '
            query ProductImageList($productId: ID!) {
                product(id: $productId) {
                    media(first: 10, query: "media_type:IMAGE", sortKey: POSITION) {
                        nodes {
                            id
                            alt
                            ... on MediaImage {
                                createdAt
                                image {
                                    width
                                    height
                                    url
                                }
                            }
                        }
                        pageInfo {
                            startCursor
                            endCursor
                        }
                    }
                }
            }
        ';
        $result = $this->query($query, ['productId' => $productId]);

        $existingImage = $result['data']['product']['media']['nodes'][0] ?? null;
        if ($existingImage) {
            $deleteMutation = '
                mutation productDeleteMedia($mediaIds: [ID!]!, $productId: ID!) {
                    productDeleteMedia(mediaIds: $mediaIds, productId: $productId) {
                        deletedMediaIds
                        deletedProductImageIds
                        mediaUserErrors {
                            field
                            message
                        }
                        product {
                            id
                            title
                            media(first: 10) {
                                nodes {
                                    alt
                                    mediaContentType
                                    status
                                }
                            }
                        }
                    }
                }
            ';
            $this->mutation($deleteMutation, [
                'mediaIds' => [ $existingImage['id'] ],
                'productId' => $productId
            ]);
        }

        $createMutation = '
            mutation productCreateMedia($media: [CreateMediaInput!]!, $productId: ID!) {
                productCreateMedia(media: $media, productId: $productId) {
                    media {
                        alt
                        mediaContentType
                        status
                    }
                    mediaUserErrors {
                        field
                        message
                    }
                    product {
                        id
                        title
                    }
                }
            }
        ';

        return $this->mutation($createMutation, [
            'productId' => $productId,
            'media' => [
                [
                    'alt' => $title,
                    'mediaContentType' => 'IMAGE',
                    'originalSource' => $newImageUrl
                ]
            ],
        ]);
    }

    public function createProductWithVariant(array $product): array
    {
        $mutation = '
            mutation productCreate($input: ProductInput!) {
                productCreate(input: $input) {
                    product {
                        id
                        title
                        vendor
                        status
                        publishedAt
                        onlineStoreUrl
                        variants(first: 1) {
                            edges {
                                node {
                                    id
                                    price
                                    sku
                                    inventoryItem {
                                        id
                                        tracked
                                    }
                                }
                            }
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        ';

        $variables = [
            'input' => [
                'title' => $product['notes'],
                'vendor' => 'KM Motos',
                'status' => 'ACTIVE',
                'published' => true,
                'variants' => [
                    [
                        'price' => number_format($product['price'], 2, '.', ''),
                        'sku' => $product['product_key'],
                    ]
                ],
            ]
        ];

        return $this->mutation($mutation, $variables);
    }

    public function getPublications(): array
    {
        $query = '
            query publications {
                publications(first: 10) {
                    edges {
                        node {
                            id
                        }
                    }
                }
            }
        ';
        return $this->query($query, []);
    }
}
