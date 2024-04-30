<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Carbon\Carbon;
use DB;
use Utils;

class SentApiWhatsapp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $event_name, $model;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($event_name, $model)
    {
        $this->event_name = $event_name;
        $this->model = $model;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        dump('Procedo a mandar mensaje por la api');
        $event_name = $this->event_name;
        $model = $this->model;
        try {
            $className = $this->getEventClass($event_name);
            if (class_exists($className)) {
                event(new $className($model));
            }
        } catch (\Exception $e) {
            dump('recogi el siguiente error: ');
            dump($e);
            dispatch((new SentApiWhatsapp($event_name, $model))->delay(1800));
        }
        dump('Termino de enviar el mensaje');
        return;
    }

    private function getEventClass($event_name)
    {
        // dump('Estoy generando la clase');
        return 'App\Events\\' . $event_name;
    }
}
