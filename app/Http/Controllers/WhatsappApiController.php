<?php

namespace App\Http\Controllers;

use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappApiController extends Controller
{
    protected WhatsappService $whatsappService;
    public function __construct(WhatsappService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function hook(Request $request)
    {
        $input = $request->all();
        $this->whatsappService->selectAction($input);
//        if($data['event'] == 'messages.upsert' && !is_null($instance_id)){
//            $message = isset($data['data'][0]['message']['extendedTextMessage']['text']) ? trim($data['data'][0]['message']['extendedTextMessage']['text']) : (isset($data['data'][0]['message']['imageMessage']['caption']) ? $data['data'][0]['message']['imageMessage']['caption'] : null );
//            $phone = isset($data['data'][0]['key']['remoteJid']) ? explode('@', $data['data'][0]['key']['remoteJid'])[0] : null;
//            if(!is_null($message) && !is_null($phone)){
//                Utils::switchReceiveMessages($message,$phone,$instance_id);
//            }
//            Log::info($instance_id);
//            Log::info($data);
//        }
        Log::info($instance_id);
        Log::info($data);
        return true;
    }
}
