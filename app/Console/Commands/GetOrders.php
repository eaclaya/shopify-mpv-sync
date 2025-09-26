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

        $orders = ShopifyGraphQL::getOrdersByNumber($orderNumber);
        $edgesOrders = $orders['data']['orders']['edges'][0]['node'];
        $arrayOrders = [];
        $lineItems = $edgesOrders['lineItems']['edges'];
        $products = [];
        foreach ($lineItems as $lineItem) {
            $products[] = [
                $lineItem['node']['quantity'],
                $lineItem['node']['variant']['sku'],
                $lineItem['node']['variant']['price'],
            ];
        }
        $arrayOrders[] = [
            'client' => $edgesOrders['customer'],
            'address' => $edgesOrders['shippingAddress'],
            'products' => $products,
        ];
        Log::info('orders: ', [ $arrayOrders ]);
        var_dump($arrayOrders);
        $this->info('Finish GetOrders command...');
        return 0;
    }
}
