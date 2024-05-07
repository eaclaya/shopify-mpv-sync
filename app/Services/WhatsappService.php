<?php
namespace App\Services;

use App\Jobs\SentApiWhatsapp;
use App\Repositories\ChatbotRepository;
use App\Services\ChatbotService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected array $phoneHouse = [2200, 2201, 2209, 2211, 2212, 2213, 2216, 2220, 2221, 2222, 2223, 2224, 2225, 2226, 2227, 2228, 2229, 2230, 2231, 2231, 2232, 2233, 2234, 2235, 2236, 2237, 2238, 2239, 2240, 2245, 2246, 2255, 2257, 2290, 2291, 2423, 2424, 2425, 2429, 2431, 2433, 2434, 2435, 2436, 2438, 2439, 2440, 2441, 2442, 2443, 2444, 2445, 2446, 2448, 2451, 2452, 2453, 2455, 2543, 2544, 2545, 2550, 2551, 2552, 2553, 2554, 2555, 2556, 2557, 2558, 2559, 2565, 2566, 2574, 2640, 2641, 2642, 2643, 2647, 2648, 2650, 2651, 2652, 2653, 2654, 2655, 2656, 2657, 2658, 2659, 2660, 2661, 2662, 2663, 2664, 2665, 2667, 2668, 2669, 2670, 2671, 2672, 2673, 2674, 2675, 2678, 2680, 2681, 2682, 2683, 2684, 2685, 2686, 2687, 2688, 2690, 2691, 2764, 2766, 2767, 2768, 2769, 2770, 2772, 2773, 2774, 2775, 2776, 2777, 2778, 2779, 2783, 2784, 2879, 2880, 2881, 2882, 2883, 2885, 2887, 2888, 2889, 2891, 2892, 2893, 2894, 2895, 2897, 2898, 2899];

    protected ChatbotRepository $chatbotRepo;
    protected ChatbotService $chatbotService;
    protected array $message_type_reference = [
        'conversation' => 0,
        'imageMessage' => 1,
        'documentMessage' => 2,
        'audioMessage' => 3,
        'videoMessage' => 4,
        'otherMessage' => 5
    ];

    protected int $social_network = 0;

    public function __construct(ChatbotRepository $chatbotRepo, ChatbotService $chatbotService)
    {
        $this->chatbotRepo = $chatbotRepo;
        $this->chatbotService = $chatbotService;
    }

    public function selectAction($input): bool|string
    {
        $instanceId = $input['instance_id'] ?? null;
        $data = $input['data']['data'] ?? null;
        $event = isset($input['data']['event']) ? explode('.', $input['data']['event']) : null;
//        Log::info('selectAction', $data);
        if(is_null($data) || is_null($event)){
            return false;
        }
        switch ($event[0]) {
            case 'messages':
                if($event[1] == 'update'){
                    $this->messagesUpdate($data,$instanceId);
                    break;
                } elseif ($event[1] == 'upsert'){
                    $this->messagesUpsert($data,$instanceId);
                    break;
                }
                return false;
            default:
                return false;
        }
        return false;
    }

    public function switchReceiveMessages($message,$phone,$instance_id): bool
    {
//        https://socializerx.com/api/set_webhook?webhook_url=https://8c1c-200-59-184-228.ngrok-free.app/&enable=true&instance_id=662150A3AE407&access_token=65ac077ef0c0a
        $message = trim($message);
        if(in_array($message, ['dar_baja','dar_alta'])){
            $phone_search = substr($phone, -8, 8);
            if($message === 'dar_baja'){
                \DB::connection('mysql_two')->table('clients')
                    ->where('phone', 'like', '%' . $phone_search . '%')
                    ->orWhere('work_phone', 'like', '%' . $phone_search . '%')->update(['receive_messages' => 0]);
            }elseif($message === 'dar_alta'){
                \DB::connection('mysql_two')->table('clients')
                    ->where('phone', 'like', '%' . $phone_search . '%')
                    ->orWhere('work_phone', 'like', '%' . $phone_search . '%')->update(['receive_messages' => 1]);
            }

            $client = \DB::connection('mysql_two')->table('clients')->select('id')
                ->where('phone', 'like', '%' . $phone_search . '%')
                ->orWhere('work_phone', 'like', '%' . $phone_search . '%')
                ->first();

            if(!$client){
                return false;
            }

            $whatsappConfig = \App\Models\WhatsappConfigAccount::where('instance_id', $instance_id)
                ->first();

            if(!$whatsappConfig->active_messages){
                return false;
            }
            if((!isset($whatsappConfig->instance_id) || trim($whatsappConfig->instance_id) == "") || (!isset($whatsappConfig->access_token) || trim($whatsappConfig->access_token) == "")){
                return false;
            }

            if ( config('app.env') !== 'production') {
                $number = '584247031071';
            }else{
                $number = $phone;
            }

            if(!$number){
                return false;
            }

            $url_link = $this->getLinkWhatsapp($client->id);

            $instance_id = $whatsappConfig->instance_id;
            $access_token = $whatsappConfig->access_token;
            if($message === 'dar_baja'){
                $message = 'ya no recibiras tus notificaciones por este medio como cliente de kmmotos, aun asi puedes volver a ativar las mismas enviando la palabra clave: dar_alta (sin espacios, y con el guion bajo), o por el siguiente enlace: '.$url_link;
                $response = $this->sentSmsClientWhatsapp($number, $message, $instance_id, $access_token);
            }elseif($message === 'dar_alta'){
                $message = 'has activado tus notificaciones por este medio como cliente de kmmotos, aun asi puedes volver a desativar las mismas enviando la palabra clave: dar_baja  (sin espacios, y con el guion bajo), o por el siguiente enlace: '.$url_link;
                $response = $this->sentSmsClientWhatsapp($number, $message, $instance_id, $access_token);
            }
            return true;
        }
        return false;
    }

    public function validateNumberClientWhatsapp($client): bool|string
    {
        if ( config('app.env') !== 'production') {
            return '584247031071';
        }
        $receive_messages = $client->receive_messages;
        if(!$receive_messages){
            return false;
        }
        return $this->validateNumberWhatsapp($client->phone, $client->work_phone);
    }

    public function validateNumberWhatsapp($phone,$work_phone = null): bool|string
    {
        if ( config('app.env') !== 'production') {
            return '584247031071';
        }

        $isPhone = true;
        $number = (isset($phone) && trim($phone) !== '') ? str_replace(" ", "", preg_replace("/[^0-9]/", "", trim($phone))) : null;
        if(is_null($number)){
            $isPhone = false;
            $number = (isset($work_phone) && trim($work_phone) !== '') ? str_replace(" ", "", preg_replace("/[^0-9]/", "", trim($work_phone))) : null;
            if(is_null($number)){
                return false;
            }
        }

        if(!isset($this->phoneHouse[intval(substr($number, 0, 4))]) && strlen($number) >= 8){
            $number = '504'.substr($number, -8, 8);
        }else{
            if($isPhone){
                $number = (isset($work_phone) && trim($work_phone) !== '') ? str_replace(" ", "", preg_replace("/[^0-9]/", "", trim($work_phone))) : null;
                if(!is_null($number) && !isset($this->phoneHouse[intval(substr($number, 0, 4))]) && strlen($number) >= 8){
                    $number = '504'.substr($number, -8, 8);
                }else{
                    return false;
                }
            }else{
                return false;
            }
        }
        return $number;
    }

    public function getLinkWhatsapp($client_id): string
    {
        $delimeter = (str_contains(substr(url('/'), 7), ':')) ? ':' : '.';
        $base_link = explode($delimeter, substr(url('/'), 7))[0];
        $base_link = (str_contains($base_link, '/')) ? substr($base_link, 1) : $base_link;

        $url_link = 'https://www.kmmotos.com/pages/client_invoice_iframe?base_link='.$base_link.'&client='.$client_id;

        return $url_link;
    }

    public function sentSmsClientWhatsapp($number, $message, $instance_id, $access_token, $dataReset = null)
    {
        $headers = [
            'Content-Type' => 'application/json',
        ];
        $data = [
            "number" => $number,
            "type" => "text",
            "instance_id" => $instance_id,
            "access_token" => $access_token,
            "message" => $message,
        ];
        try {
            $client = new \GuzzleHttp\Client([ 'headers' => $headers, 'timeout' => 4, 'connect_timeout' => 4]);
            $response = $client->request('GET', 'https://socializerx.com/api/send', [
                'query' => $data,
                'timeout' => 4,
                'connect_timeout' => 4
            ]);
            if($response->getStatusCode() == 200){
                $body = $response->getBody();
                $dataResponse = json_decode((string) $body, true);
                if($dataResponse['status'] == "error"){
                    if(isset($dataReset)){
                        $dataReset['error'] = trim($dataResponse['message']);
                        $body = trim($dataResponse['message']);
                        if($body !== 'ID de instancia no validada'){
                            dispatch((new SentApiWhatsapp($dataReset['event'], $dataReset['model']))->delay(1800));
                        }
                    }
                }
                if(isset($dataReset)){
                    $dataReset['success'] = substr($body, 0, 250);
                    $whatsappErrors = new \App\Models\WhatsappErrors();
                    $whatsappErrors->saveFirstOrNew($dataReset);
                }

            }else{
                $body = 'Estatus: '.$response->getStatusCode();
                if(isset($dataReset)){
                    $dataReset['error'] = $body;
                    $whatsappErrors = new \App\Models\WhatsappErrors();
                    $whatsappErrors->saveFirstOrNew($dataReset);
                    dispatch((new SentApiWhatsapp($dataReset['event'], $dataReset['model']))->delay(1800));
                }
            }
        } catch (\Exception $e) {
            $body = $e;
            if(isset($dataReset)){
                $dataReset['error'] = substr($body, 0, 250);
                $body = $dataReset['error'];
                $whatsappErrors = new \App\Models\WhatsappErrors();
                $whatsappErrors->saveFirstOrNew($dataReset);
                dispatch((new SentApiWhatsapp($dataReset['event'], $dataReset['model']))->delay(1800));
            }
        }
        return $body;
    }

    public function messagesUpdate($data,$instanceId): bool|string
    {
        $success = '';
        foreach ($data as $item) {
            $status = $item['update']['status'] ?? '';
            $fromMe = $item['key']['fromMe'] ?? false;
            if (!$fromMe) {
                continue;
            }

            $phone = $this->clearRemoteJid($item['key']['remoteJid'] ?? null);

            if($status == 3){
                $success = 'me notifican que les llego el mensaje';
            }elseif($status == 4){
                $success = 'me notifican que leyeron el mensaje';
            }elseif($status >= 5){
                $success = 'me notifican que vieron la media';
            }
        }
        return trim($success) !== '' ? trim($success) : false;
    }

    public function messagesUpsert($data,$instanceId): bool|string
    {
        foreach ($data as $item) {
            $fromMe = $item['key']['fromMe'] ?? false;
            if($fromMe){
//                $this->upsertFromMe($item,$instanceId);
                Log::info('fromMe', $item);
            }else{
                $this->upsert($item,$instanceId);
            }
        }
        return false;
    }

    public function upsertFromMe($item,$instanceId): bool|string{
        $phone = $item['key']['remoteJid'] ?? null;
        $phone = $this->clearRemoteJid($phone);
        $phone = $this->validateNumberWhatsapp($phone);
        $messageId = $item['key']['id'] ?? null;
        if(isset($item['message']['extendedTextMessage'])){
            $message = trim($item['message']['extendedTextMessage']['text']) ?? null;
            $referencedMessageId = trim($item['message']['extendedTextMessage']['contextInfo']['stanzaId']) ?? null;
        }else{
            $message = trim($item['message']['conversation']) ?? null;
        }
        $messageType = $this->searchMessageType($item['message']);
        /* aca respondi un mensaje, por lo tanto procedo a guardar*/
        return true;
    }

    public function upsert($item,$instance): bool|string{
        $protocolType = Arr::get($item, 'message.protocolMessage.type') ?? false;
        if($protocolType !== false){
            Log::info('upsert',['protocolType']);
            return false;
        }
        $contact = Arr::get($item, 'key.remoteJid');
        $contact = $this->clearRemoteJid($contact);
        if(is_null($contact)){
            Log::info('upsert',['contact_null']);
            return false;
        }
        $message_id = Arr::get($item, 'key.id');
        if(isset($item['message']['extendedTextMessage'])){
            $received_message_text = trim(Arr::get($item, 'message.extendedTextMessage.text'));
            $referenced_message_id = trim(Arr::get($item, 'message.extendedTextMessage.contextInfo.stanzaId'));
        }else{
            $received_message_text = trim(Arr::get($item, 'message.conversation'));
            $referenced_message_id = null;
        }
        $isSwitch = $this->switchReceiveMessages($received_message_text,$contact,$instance);
        if ($isSwitch) {
            Log::info('isSwitch',[true]);
            return false;
        }
        $searchMessageType = $this->searchMessageType(Arr::get($item, 'message'));
        $message_type = $this->message_type_reference[$searchMessageType] ?? 5;
        $media_file = null;
        if($message_type > 0 && $message_type < 5){
            $media_file = $this->getMedia(Arr::get($item, 'message.'.$searchMessageType));
        }
        $status = 0;
        $data = [
            "social_network" => $this->social_network,
            "instance" => $instance,
            "contact" => $contact,
            "status" => $status,
            "message_type" => $message_type,
            "message_id" => $message_id,
            "referenced_message_id" => $referenced_message_id,
            "received_message_text" => $received_message_text,
            "media_file" => $media_file
        ];

        Log::info('finish upsert',$data);

        $chat = $this->chatbotRepo->save($data);
        Log::info('finish upsert CHAT', [$chat]);
        $this->chatbotService->selectAction($chat);
        return true;
    }

    /**
     * @return array
     */
    public function clearRemoteJid($remoteJid): string|null
    {
        $phone = $remoteJid ? explode('@', $remoteJid)[0] : null;
        $phone = ((isset($phone) && trim($phone) !== '') && str_contains($phone, ":")) ? explode(":", $phone)[0] : $phone;

        if (preg_match('/[a-zA-Z]/', $phone)) {
            Log::info('clearRemoteJid',['error' => $phone]);
            return null;
        }
        return $phone;
    }

    public function searchMessageType($message): string{
        if (isset($message['imageMessage'])) {
            return 'imageMessage';
        } elseif (isset($message['documentMessage'])) {
            return 'documentMessage';
        } elseif (isset($message['audioMessage'])) {
            return 'audioMessage';
        } elseif (isset($message['videoMessage'])) {
            return 'videoMessage';
        } elseif (isset($message['conversation'])) {
            return 'conversation';
        } else {
            return 'otherMessage';
        }
    }

    public function getMedia($media): string {
        $data = [];
        if(isset($media['url'])){
            $data['url'] = $media['url'];
        }
        if(isset($media['mediaKey'])){
            $data['mediaKey'] = $media['mediaKey'];
        }
        if(isset($media['mimetype'])){
            $data['mimetype'] = $media['mimetype'];
        }
        if(isset($media['mediaKeyTimestamp'])){
            $data['mediaKeyTimestamp'] = $media['mediaKeyTimestamp'];
        }
        return json_encode($data,true);
    }
}
