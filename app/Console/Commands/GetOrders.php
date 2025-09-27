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
        $orderNumber = !isset($orderNumber) ? 2591 : $orderNumber;
        $this->info('Running GetOrders command...');

        $orders = ShopifyGraphQL::getOrdersByNumber($orderNumber);
        var_dump('$orders');
        var_dump($orders);
        var_dump('----');
        $edgesOrders = $orders['data']['orders']['edges'][0]['node'];
        $lineItems = $edgesOrders['lineItems']['edges'];
        $taxLines = $edgesOrders['taxLines'][0]['rate'];
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
            'rate' => $taxLines,
        ];
        Log::info('orders: ', [ $result ]);
        var_dump($result);
        $this->info('Finish GetOrders command...');
        return 0;
    }
}
