<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

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

    protected function toGlobalId(string $resourceName, int $id): string
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
                productVariants(first: 1, query: $sku) {
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
                    inventoryLevels {
                        id
                        available
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
                'inventoryItemAdjustments' => [
                    [
                        'inventoryItemId' => $inventoryItemId,
                        'locationId' => $locationGlobalId,
                        'availableQuantity' => $newQuantity
                    ]
                ]
            ]
        ];

        return $this->query($mutation, $variables);
    }

    public function updateProductTitleAndBody(string $productId, string $title, string $bodyHtml, string $imageUrl): array
    {
        $mutation = '
            mutation productUpdate($input: ProductInput!) {
                productUpdate(input: $input) {
                    product {
                        id
                        title
                        bodyHtml
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
                'bodyHtml' => $bodyHtml,
            ]
        ];

        return $this->mutation($mutation, $variables);
    }

    public function replaceProductImage(string $productId, string $newImageUrl): array
    {
        $query = '
            query getProductImages($productId: ID!) {
                product(id: $productId) {
                    images(first: 1) {
                        edges {
                            node {
                                id
                                originalSrc
                            }
                        }
                    }
                }
            }
        ';
        $result = $this->query($query, ['productId' => $productId]);

        $existingImage = $result['data']['product']['images']['edges'][0]['node'] ?? null;

        if ($existingImage) {
            $deleteMutation = '
                mutation productImageDelete($id: ID!) {
                    productImageDelete(id: $id) {
                        deletedImageId
                        userErrors {
                            field
                            message
                        }
                    }
                }
            ';
            $this->mutation($deleteMutation, ['id' => $existingImage['id']]);
        }

        $createMutation = '
            mutation productImageCreate($productId: ID!, $src: String!) {
                productImageCreate(productId: $productId, image: {src: $src}) {
                    image {
                        id
                        originalSrc
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        ';

        return $this->mutation($createMutation, [
            'productId' => $productId,
            'src' => $newImageUrl,
        ]);
    }
}
