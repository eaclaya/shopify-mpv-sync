<?php

namespace App\Jobs;

// use App\Repositories\ProductGraphQLRepository;
use App\Repositories\SupabaseRepository;
use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

class SentApiSupabase implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private $entity;
    private $tableName;

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
    public function __construct($entity, $tableName)
    {
        $this->entity = $entity;
        $this->tableName = $tableName;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SupabaseRepository $productRepository)
    {
        dump('Procedo a mandar mensaje por la api');
        $entity = $this->entity;
        $tableName = $this->tableName;
        try {
            $productRepository->update($tableName, $entity['supabase_id'], $entity);
        } catch (\Exception $e) {
            dump('recogi el siguiente error: ');
            dump($e);
        }
        dump('Termino de enviar el mensaje');
        return;
    }
}
