<?php

namespace App\Console\Commands;

use App\Facades\ShopifyGraphQL;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GetOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:orders {orderNumber?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get and process orders (customize this description)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $orderNumber = $this->argument('orderNumber');
        $orderNumber = !isset($orderNumber) ? 2594 : $orderNumber;
        $this->info('Running GetOrders command...');

        $publications = ShopifyGraphQL::getOrdersByNumber();
        // $edgesPublications = $publications['data']['publications']['edges'];
        // $arrayPublications = [];
        // foreach ($edgesPublications as $edge) {
        //     $arrayPublications[] = [
        //         'publicationId' => $edge['node']['id'],
        //         'publishDate' => null,
        //     ];
        // }
        // Log::info('publications: ', [ $arrayPublications ]);
        var_dump($publications);
        $this->info('Finish GetOrders command...');
        return 0;
    }
}
