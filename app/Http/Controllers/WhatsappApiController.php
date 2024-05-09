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

    public function hook(Request $request): bool
    {
        $input = $request->all();
        Log::info('Whatsapp Webhook Init');
        $this->whatsappService->selectAction($input);
        return true;
    }
}
