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
            $qty = $lineItem['node']['currentQuantity'];
            if ($qty == 0) {
                continue;
            }
            $currentPrice = $price = $lineItem['node']['variant']['price'];
            $discountPrice = isset($lineItem['node']['discountedTotalSet']['shopMoney']['amount']) && !empty($lineItem['node']['discountedTotalSet']['shopMoney']['amount'])
                    ? $lineItem['node']['discountedTotalSet']['shopMoney']['amount'] : null;
            if (isset($discountPrice) && $discountPrice < $price) {
                $currentPrice = $discountPrice;
            }
            $products[] = [
                'product_key' => $lineItem['node']['variant']['sku'],
                'qty' => $qty,
                'price' => number_format($currentPrice, 2),
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
