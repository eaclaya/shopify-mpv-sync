<?php

namespace App\Jobs;

use App\Repositories\ProductGraphQLRepository;
use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

// use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

class SentApiShopifyGraphQL implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private $product;

    /**
     * The time (in seconds) that the lock should be maintained.
     *
     * @var int
     */
    // public $uniqueFor = 60; // 1 hora

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($product)
    {
        $this->product = $product;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(ProductGraphQLRepository $productRepository)
    {
        dump('Procedo a mandar mensaje por la api');
        $product = $this->product;
        try {
            $productRepository->update($product);
        } catch (\Exception $e) {
            dump('recogi el siguiente error: ');
            dump($e);
        }
        dump('Termino de enviar el mensaje');
        return;
    }
}
