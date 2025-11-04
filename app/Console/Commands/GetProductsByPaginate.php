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
        // $edgesPublications = $publications['data']['publications']['edges'];
        // $arrayPublications = [];
        // foreach ($edgesPublications as $edge) {
        //     $arrayPublications[] = [
        //         'publicationId' => $edge['node']['id'],
        //         'publishDate' => null,
        //     ];
        // }
        Log::info('products: ', [ $products ]);
        var_dump($products);
        $this->info('Finish GetProductsByPaginate command...');
        return 0;
    }
}
