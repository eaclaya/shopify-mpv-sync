<?php
namespace App\Services;

use App\Jobs\SentApiWhatsapp;
use Illuminate\Support\Facades\Http;
use App\Repositories\ChatbotRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

use OpenAI;

class ChatbotService
{
    protected ChatbotRepository $chatbotRepository;

    protected string $apiKeyOpenIa;
    protected string $assistantsId;

    protected array $typeActionsReferences = [
         0 => 'ask_if_user_wants_to_be_attended',
         1 => 'ask_what_user_needs',
         2 => 'thank_user_and_wait_for_advisor',
         3 => 'thank_user_and_finish_chat',
         4 => 'start_chatbot_conversation',
    ];

    public function __construct(ChatbotRepository $chatbotRepository)
    {
        $this->chatbotRepository = $chatbotRepository;
        $this->apiKeyOpenIa = config('services.open_ia.api_key');
        $this->assistantsId = config('services.open_ia.assistant');
    }

    public function selectAction($chat): bool
    {
        $lastMessage = $this->chatbotRepository->getLastChatMessage($chat);
        Log::info('chat bot service lastMessage', [$lastMessage]);
        if(isset($lastMessage)){
            switch ($lastMessage->status){
            case 0:
                $response_message = $this->processChat($chat, 0, null);
                $this->chatbotRepository->update($chat, [
                    'status' => 2,
                    'response_message' => $response_message
                ]);
                break;
            case 1:
            case 3:
                $this->chatbotRepository->update($chat, [
                    'status' => 0,
                ]);
                break;
            case 2:
                Log::info('ultimo status 2', [$chat['received_message_text']]);
                if($this->removeAccentsAndSymbols($chat['received_message_text']) == 'si'){
                    $response_message = $this->processChat($chat, 1, null);
                    $this->chatbotRepository->update($chat, [
                        'status' => 4,
                        'response_message' => $response_message
                    ]);
                }elseif($this->removeAccentsAndSymbols($chat['received_message_text']) == 'no'){
                    $response_message = $this->processChat($chat, 2, null);
                    $this->chatbotRepository->update($chat, [
                        'status' => 3,
                        'response_message' => $response_message
                    ]);
                }elseif($this->removeAccentsAndSymbols($chat['received_message_text']) == 'finalizar'){
                    $response_message = $this->processChat($chat, 3, null);
                    $this->chatbotRepository->update($chat, [
                        'status' => 3,
                        'response_message' => $response_message
                    ]);
                    if(isset($lastMessage->thread) && trim($lastMessage->thread) !== ""){
                        $client = OpenAI::client($this->apiKeyOpenIa);
                        $client->threads()->delete($lastMessage->thread);
                    }
                }else{
                    $response_message = $this->processChat($chat, 0, null);
                    $this->chatbotRepository->update($chat, [
                        'status' => 2,
                        'response_message' => $response_message
                    ]);
                }
                break;
            case 4:
                if($this->removeAccentsAndSymbols($chat['received_message_text']) == 'finalizar'){
                    $response_message = $this->processChat($chat, 3, null);
                    $this->chatbotRepository->update($chat, [
                        'status' => 3,
                        'response_message' => $response_message
                    ]);
                    if(isset($lastMessage->thread) && trim($lastMessage->thread) !== ""){
                        $client = OpenAI::client($this->apiKeyOpenIa);
                        $client->threads()->delete($lastMessage->thread);
                    }
                }else{
                    $response_message = $this->processChat($chat, 4, $lastMessage);
                    $this->chatbotRepository->update($chat, [
                        'status' => 4,
                        'response_message' => $response_message
                    ]);
                }
                break;
        }
        }else{
            $this->chatbotRepository->update($chat, [
                'status' => 0,
            ]);
        }
        return true;
    }

    public function makeHttRequest($social_network, $data = [], $method = 'get'): bool
    {
        Log::info('makeHttRequest init');
        try{
            $response = Http::withHeaders($this->getHeaders())
                        ->withOptions(
                            $this->getOptions()
                        )
                        ->$method(
                            $this->getEndpoint($social_network),
                            $data,
                        );
            Log::info('makeHttRequest response', [$response]);
            $responseData = ['success' => $response, 'data' => $data];
        }catch(\Exception $e){
            Log::info('makeHttRequest error', [$e]);
            $responseData = ['errors' => $e, 'data' => $data];
        }
        return $this->processMakeHttRequest($responseData);
    }

    protected function getOptions(): array
    {
        return [
            'timeout' => 15,
            'connect_timeout' => 15,
        ];
    }

    protected function getEndpoint($social_network): string
    {
        switch ($social_network){
            case 0:
            default:
                return "https://socializerx.com/api/send";
        }
    }

    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
        ];
    }

    protected function processChat($chat, $typeAction, $lastMessage = null): string
    {
        if ($chat['social_network'] == 0){
            Log::info('processChat');
            $infoInstance = $this->chatbotRepository->getInfoInstance(Arr::get($chat,'instance'));
            Log::info('processChat $infoInstance', [$infoInstance]);
            $accessToken = Arr::get($infoInstance,'access_token');
            $accountId = Arr::get($infoInstance,'account_id');
            $data = [
                "number" => Arr::get($chat,'contact'),
                "type" => "text",
                "instance_id" => Arr::get($chat,'instance'),
                "access_token" => $accessToken,
                "message" => $this->getChatMessage($chat,$typeAction,$accountId,$lastMessage)
            ];
            Log::info('processChat data', $data);
            $isError = $this->makeHttRequest($chat['social_network'], $data, 'get');
            $this->chatbotRepository->update($chat, [
                'sent_error' => $isError,
            ]);
            return $data["message"];
        }
        return '';
    }

    public function getChatMessage($chat,$typeAction,$accountId=null,$lastMessage=null): string|object
    {
        if($chat->message_type !== 0){
            return 'Por el momento no puedo procesar mensajes de tipo multimedia. Por favor envia un mensaje de texto para que pueda brindarte una mejor asistencia.';
        }
        return match ($typeAction) {
            0 => "Hola, soy 'Asistant' un Asistente de inteligencia artificial, ¿Deseas Ser Atendido por mi?, Responde Si para continuar o No para esperar a un Empleado.",
            1 => "Gracias por preferirme, ¿en qué puedo ayudarte?, recuerda que puedes escribir 'finalizar' para terminar la conversación.",
            2 => "Gracias por tu tiempo, en breve te atendera un empleado.",
            3 => "Gracias por tu tiempo, hemos finalizado la conversación.",
            default => $this->chatWithOpenIa($chat, $lastMessage, $accountId),
        };
    }

    protected function chatWithOpenIa($chat,$lastMessage = null,$accountId = null): string
    {
        $received_message_text = $this->getJsonMessage($chat->received_message_text);
        Log::info('chatWithOpenIa', [$chat,$lastMessage,$accountId]);
        $client = OpenAI::client($this->apiKeyOpenIa);
        $threadId = (isset($lastMessage->thread) && trim($lastMessage->thread) !== "") ? trim($lastMessage->thread) : null;
        if(!is_null($threadId)){
            $this->messageThreadExist($client,$threadId,$received_message_text);
        }else{
            $newMessage = $this->newMessageThread($client,$received_message_text);
            $threadId = (isset($newMessage->threadId) && trim($newMessage->threadId) !== "") ? trim($newMessage->threadId) : null;
        }
        if (!is_null($threadId)){
            sleep(3);
            Log::info('chatWithOpenIa threadId', [$threadId]);
            $chat->thread = $threadId;
            $chat->save();
            $iaMessage = $this->retrieveLastIaMessage($client,$threadId);
            Log::info('chatWithOpenIa response', [$iaMessage]);
            $arrResp = json_decode($iaMessage,true);
            Log::info('chatWithOpenIa array response', [$iaMessage]);
            return $this->evalResponse($arrResp,$chat,$accountId);
        }
        return 'Hemos tenido dificultades en el servidor de IA, por favor intenta de nuevo.';
    }

    protected function messageThreadExist($client,$thread,$received_message_text): bool
    {
        Log::info('messageThreadExist create message', [$thread,$received_message_text,$this->assistantsId]);
        $messageResponse = $client->threads()->messages()->create($thread, [
            'role' => 'user',
            'content' => $received_message_text,
        ]);
        Log::info('messageThreadExist finish message', [$messageResponse]);
        Log::info('messageThreadExist create run thread');
        $runResponse = $client->threads()->runs()->create(
            threadId: $thread,
            parameters: [
                'assistant_id' => $this->assistantsId,
            ],
        );
        Log::info('messageThreadExist finish run thread', [$runResponse]);
        return true;
    }

    protected function newMessageThread($client,$received_message_text){
         Log::info('newMessageThread create thread',[$received_message_text,$this->assistantsId]);
         $createAndRunResponse = $client->threads()->createAndRun(
            [
                'assistant_id' => $this->assistantsId,
                'thread' => [
                    'messages' =>
                        [
                            [
                                'role' => 'user',
                                'content' => $received_message_text,
                            ],
                        ],
                ],
            ],
        );
        Log::info('newMessageThread finish thread', [$createAndRunResponse]);
        return $createAndRunResponse;
    }

    protected function evalResponse($arrResp,$chat,$accountId):string {
        $arrResp = collect($arrResp)->toArray();
        $action = Arr::get($arrResp,'action') ?? null;
        $returnResponse = Arr::get($arrResp, 'response') ?? '';
        $product = Arr::get($arrResp, 'product') ?? null;
        Log::info('evalResponse', [$product]);
        if($action == 'search'){
            $returnResponse .= $this->consultProduct($product,$accountId,$chat->thread);
        }
        $this->chatbotRepository->update($chat, [
            'status' => 4,
            'response_message' => $returnResponse,
        ]);
        return $returnResponse;
    }

    protected function consultProduct($product,$accountId,$thread): string
    {
        if(!isset($product)){
          return '';
        }
        $account = $this->chatbotRepository->getAccountName($accountId);

        $productName = Arr::get($product,'name') ?? null;
        $productModel = Arr::get($product,'model') ?? null;
        $productBrand = Arr::get($product,'brand') ?? null;

        Log::info('consultProduct', [$productName, $productModel, $productBrand]);

        $categories = $this->chatbotRepository->getIdsModel($productName, 'categories');
        $vendors = $this->chatbotRepository->getIdsModel($productBrand, 'vendors');
        $brands = $this->chatbotRepository->getIdsModel($productBrand, 'brands');

        Log::info('consultProduct', [$categories,$vendors,$brands]);

        $products = $this->chatbotRepository->searchProducts($productName, $productModel, $productBrand, $categories, $vendors, $brands, $accountId, $thread, true);

        $result = '';
        if($products->count() > 0){
            $result .= "\n\n" . 'En la tienda ' . $account . ' tenemos los siguientes productos: ' . "\n";
            foreach ($products as $product) {
                $result .= '*' . trim($product->product_key) . '*, Descripcion: ' . trim($product->description) . ' *Disponible*, Valor aproximado: ' . $product->price . "\n";
            }
            $result .= "\n";
        }

        $productsOthers = $this->chatbotRepository->searchProducts($productName, $productModel, $productBrand, $categories, $vendors, $brands, $accountId, $thread, false);

        if($productsOthers->count() > 0){
            $result .= 'En otras tiendas tenemos los siguientes productos: ' . "\n";
            foreach ($productsOthers as $product) {
                $result .= 'Codigo: ' . $product->product_key . ', ' . $product->description . ' *Disponible*, Tiene un valor alrededor de: ' . $product->price . "\n";
            }
            $result .= "\n". "Puedes ir la tienda " . $account . " para asegurar la disponivilidad, recuerda que puedes finalizar la consulta escribiendo la palabra: Finalizar";
        }
        return $result;
    }

    protected function retrieveLastIaMessage($client,$threadId,$count=0): string
    {
        if($count > 5){
            return 'he tenido dificultades para solventar tu requerimiento, por favor vuelve a intentarlo';
        }
        $count++;
        $response = $client->threads()->messages()->list($threadId, ["limit" => 1]);
        Log::info('retrieveLastIaMessage', [$response]);
        $text = '';

        foreach ($response->data as $result) {
            if($response->lastId !== $result->id){
                continue;
            }
            Log::info('retrieveLastIaMessage', [$result]);
            foreach ($result->content as $content) {
                if($content->type === 'text'){
                    $text = $content->text->value;
                    break;
                }
            }
        }
        if(trim($text) === ""){
            sleep(2);
            $text = $this->retrieveLastIaMessage($client,$threadId,$count);
        }
        return $text;
    }

    protected function removeAccentsAndSymbols($text): array|string|null
    {
        $unwanted_array = array(
            'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ú'=>'u',
            'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ú'=>'U',
            'ñ'=>'n', 'Ñ'=>'N', 'ä'=>'a', 'ë'=>'e', 'ï'=>'i',
            'ö'=>'o', 'ü'=>'u', 'Ä'=>'A', 'Ë'=>'E', 'Ï'=>'I',
            'Ö'=>'O', 'Ü'=>'U'
        );
        $text = strtr($text, $unwanted_array);
        $text = strtolower($text);
        return preg_replace('/[^a-z]/', '', $text);

    }

    protected function getJsonMessage($message): string
    {
        $categories = $this->chatbotRepository->varSystem('categories');
        $vendors = $this->chatbotRepository->varSystem('vendors');
        $brands = $this->chatbotRepository->varSystem('brands');
        $data = [
            'message' => $message,
            'categories' => $categories,
            'vendors' => $vendors,
            'brands' => $brands
        ];
        return json_encode($data);
    }

    protected function processMakeHttRequest($data): bool
    {
        if(isset($data['success'])){
            $response = $data['success'];
            Log::info('make Success',[$response]);
            if($response->getStatusCode() == 200){
                Log::error('is status 200');
                $body = $response->getBody();
                $dataResponse = json_decode((string) $body, true);
                if($dataResponse['status'] == "error"){
                    Log::error('is status error',[$dataResponse]);
                    return true;
                }
                return false;
            }else{
                $body = 'Estatus: '.$response->getStatusCode();
                Log::error('not is status 200', [$body]);
                return true;
            }
        }elseif(isset($data['error'])){
            $body = $data['error'];
            Log::error('make Error',[$body]);
            return true;
        }
        return true;
    }
}
