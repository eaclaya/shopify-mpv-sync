<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Console\Command;

class ShopifyProductUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify:products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shopify Product Update';

    public function __construct(ProductRepository $productRepository)
    {
        parent::__construct();
        $this->productRepository = $productRepository;
    }
    
    
    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $products = Product::sync()->get();
        foreach($products as $product){
            $this->productRepository->update($product);
        }
    }
}
