<?php

namespace App\Console\Commands;

use App\Facades\ShopifyGraphQL;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GetPublications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:publications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get and process publications (customize this description)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Acá va la lógica que quieras ejecutar
        $this->info('Running GetPublications command...');

        $publications = ShopifyGraphQL::getPublications();
        $edges = $publications['data']['publications']['edges'];
        $arrayPublications = [];
        foreach ($edges as $edge) {
            $arrayPublications[] = [
                'publicationId' => $edge['node']['id'],
                'publishDate' => null,
            ];
        }
        Log::info('publications: ', [ $arrayPublications ]);
        var_dump($arrayPublications);
        $this->info('Finish GetPublications command...');
        return 0;
    }
}
