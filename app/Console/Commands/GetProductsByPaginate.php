<?php

namespace App\Console\Commands;

use App\Facades\ShopifyGraphQL;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GetProductsByPaginate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get and process products (by paginate)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Acá va la lógica que quieras ejecutar
        $this->info('Running GetProductsByPaginate command...');

        $products = ShopifyGraphQL::getProductsByPaginate(50, 'eyJsYXN0X2lkIjo3NzYwMTQyNjMxMTQwLCJsYXN0X3ZhbHVlIjoiNzc2MDE0MjYzMTE0MCJ9');
        $edgesProducts = $products['products']['edges'];
        $arrayProducts = [];
        $arrayProducts['pageInfo'] = $products['products']['pageInfo'];
        $arrayProducts['products'] = [];
        foreach ($edgesProducts as $edge) {
            $arrayProducts['products'][] = [
                'productId' => $edge['node']['id'],
                'title' => $edge['node']['title'],
                'variantId' => $edge['node']['variants']['edges'][0]['node']['id'],
                'price' => $edge['node']['variants']['edges'][0]['node']['price'],
                'qty' => $edge['node']['variants']['edges'][0]['node']['inventoryQuantity'],
                'sku' => $edge['node']['variants']['edges'][0]['node']['sku'],
            ];
        }
        Log::info('products: ', [ $arrayProducts ]);
        var_dump($arrayProducts);
        $this->info('Finish GetProductsByPaginate command...');
        return 0;
    }
}
