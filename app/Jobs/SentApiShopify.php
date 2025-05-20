<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

class SentApiShopify implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private $product;
    private $productRepository;

    /**
     * The time (in seconds) that the lock should be maintained.
     *
     * @var int
     */
    public $uniqueFor = 60; // 1 hora

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($product, $productRepository)
    {
        $this->product = $product;
        $this->productRepository = $productRepository;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        dump('Procedo a mandar mensaje por la api');
        $product = $this->product;
        try {
            $this->productRepository->update($product);
        } catch (\Exception $e) {
            dump('recogi el siguiente error: ');
            dump($e);
        }
        dump('Termino de enviar el mensaje');
        return;
    }
}
