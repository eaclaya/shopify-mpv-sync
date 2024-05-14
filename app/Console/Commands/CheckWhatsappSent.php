<?php

namespace App\Console\Commands;

use App\Repositories\ChatbotRepository;
use App\Services\WhatsappService;
use App\Services\ChatbotService;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Storage;

class CheckWhatsappSent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:whatsapp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'check whatsapp message sent';

    protected ChatbotRepository $chatbotRepo;
    protected WhatsappService $whatsappService;
    protected ChatbotService $chatbotService;

    public function __construct(ChatbotRepository $chatbotRepo, WhatsappService $whatsappService, ChatbotService $chatbotService)
    {
        parent::__construct();
        $this->chatbotRepo = $chatbotRepo;
        $this->whatsappService = $whatsappService;
        $this->chatbotService = $chatbotService;
    }


    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $chatbotsInstances = $this->chatbotRepo->checkUnansweredMessages();
/*        $jsonChatbots = json_encode($chatbotsInstances, JSON_PRETTY_PRINT);
        Storage::disk('local')->put('archivo.json', $jsonChatbots);*/
        foreach ($chatbotsInstances as $instanceId => $instances) {
            foreach ($instances as $contact) {
                if(count($contact) < 2){
                    $this->sentMessage($contact[0]);
                    continue;
                }
                if(intval($contact[0]->id) !== intval($contact[1]->id)+1){
                    $contact[1]->verify_response = true;
                    $contact[1]->save();
                }
                $this->sentMessage($contact[0]);
            }
        }
        return 0;
    }
    private function sentMessage($chat): void
    {
        $chat->fresh();

        $isActive = $chat->verifyActive();
        dump($isActive);
        if($isActive){
            $socialNetwork = Arr::get($chat, 'social_network');
            $number = Arr::get($chat, 'contact');
            $message = Arr::get($chat, 'response_message') ?? '';
            $instance_id = Arr::get($chat, 'instance');
            if($socialNetwork === 0){
                $infoInstance = $this->chatbotRepo->getInfoInstance(Arr::get($chat,'instance'));
                $access_token = Arr::get($infoInstance,'access_token');
                $isError = $this->whatsappService->sentSmsClientWhatsapp($number, $message, $instance_id, $access_token, null);

                $this->chatbotRepo->update($chat, [
                    'sent_error' => $isError,
                ]);
            }
        }
    }
}
