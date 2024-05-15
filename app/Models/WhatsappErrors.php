<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class WhatsappErrors extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql_two';

    public $timestamps = true;

    protected $table = 'whatsapp_errors';

    /**
     * @var array
     */
    protected $fillable = [
        'event',
        'model',
        'model_id',
        'account_id',
        'attempts',
        'error',
        'error_at',
        'is_send',
    ];

    public function getModel(){
        $class = "App\\Models\\" . ucwords($this->model);
        return $class::find($this->model_id);
    }
    public function saveFirstOrNew($dataReset = null){
        Log::info('entro a whatsapp error');
        if(isset($dataReset) && count($dataReset) > 0){
            Log::info('tengo datareset');
            Log::info($dataReset['event']);
            $date = new \Datetime();
            $created_at = $date->format('Y-m-d');
            $whatsappErrors = WhatsappErrors::where('event',$dataReset['event'])
                                                ->where('model',$dataReset['model']
                                                ->getEntityType())->where('model_id', $dataReset['model']->id)
                                                ->first();
            if(isset($whatsappErrors)){
                Log::info('existe un whatsapp error');
                if(isset($dataReset['error'])){
                    Log::info('existe un error');
                    $whatsappErrors->attempts = $whatsappErrors->attempts + 1;
                    $whatsappErrors->error .= "\n \n".$whatsappErrors->attempts.': '.trim($dataReset['error']);
                    $whatsappErrors->error_at = $created_at;
                }else{
                    Log::info('no existe un error');
                    $whatsappErrors->attempts = $whatsappErrors->attempts + 1;
                    $whatsappErrors->is_send = 1;
                }
                Log::info('procedo a guardar');
                $whatsappErrors->save();
                return $whatsappErrors;
            }
            else
            {
                Log::info('no existe un whatsapp error');
                $this->event = $dataReset['event'];
                $this->model = $dataReset['model']->getEntityType();
                $this->model_id = $dataReset['model']->id;
                $this->account_id = $dataReset['model']->account_id;
                $this->attempts = 1;
                $this->error_at = $created_at;
                if(isset($dataReset['error'])){
                    Log::info('existe un error');
                    $this->error = trim($dataReset['error']);
                    $this->is_send = false;
                }else if(isset($dataReset['success'])){
                    Log::info('existe un success');
                    $this->error = trim($dataReset['success']);
                    $this->is_send = true;
                }
                Log::info('procedo a guardar');
                $this->save();
                return $this;
            }
        }
        return false;
    }
}
