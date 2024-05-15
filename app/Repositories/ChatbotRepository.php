<?php
namespace App\Repositories;

use App\Models\ChatBot;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ChatbotRepository
{
    public function save($message): ChatBot
    {
        $chatbot = new ChatBot();
        $chatbot->social_network = Arr::get($message, 'social_network');
        $chatbot->instance = Arr::get($message, 'instance');
        $chatbot->contact = Arr::get($message, 'contact');
        $chatbot->status = Arr::get($message, 'status');
        $chatbot->message_type = Arr::get($message, 'message_type');
        $chatbot->message_id = Arr::get($message, 'message_id');
        $chatbot->referenced_message_id = Arr::get($message, 'referenced_message_id') ?? '';
        $chatbot->received_message_text = Arr::get($message, 'received_message_text') ?? '';
        $chatbot->response_message = Arr::get($message, 'response_message') ?? '';
        $chatbot->media_file = Arr::get($message, 'media_file') ?? '';
        $chatbot->save();
        return $chatbot;
    }

    public function filter($filter): \Illuminate\Database\Eloquent\Collection|array
    {
        $chatbot = ChatBot::query();
        if(isset($filter['status'])){
            $chatbot->where('status', $filter['status']);
        }
        if(isset($filter['message_id'])){
            $chatbot->where('message_id', $filter['message_id']);
        }
        if(isset($filter['referenced_message_id'])){
            $chatbot->where('referenced_message_id', $filter['referenced_message_id']);
        }
        return $chatbot->get();
    }

    public function update($chat,$data): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null {
        Log::info('chatbotRepo updated init, chat data', [$chat,$data]);
        $message_id = Arr::get($chat, 'message_id') ?? Arr::get($chat, 'referenced_message_id');
        $chatbot = ChatBot::query()->where('message_id', $message_id)->first();
        Log::info('chatbotRepo updated, chatbot model init', [$chatbot]);
        $chatbot->status = Arr::get($data, 'status')? Arr::get($data, 'status') : $chatbot->status;
        $chatbot->response_message = (Arr::get($data, 'response_message') && trim(Arr::get($data, 'response_message')) !== '') ? Arr::get($data, 'response_message') : $chatbot->response_message;
        $chatbot->save();
        Log::info('chatbotRepo updated, chatbot model finish', [$chatbot]);
        return $chatbot;
    }

    public function getLastChatMessage($message): \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|null
    {
        $chatbot = ChatBot::query();
        if(isset($message['social_network'])){
            $chatbot->where('social_network', $message['social_network']);
        }
        if(isset($message['instance'])){
            $chatbot->where('instance', $message['instance']);
        }
        if(isset($message['contact'])){
            $chatbot->where('contact', $message['contact']);
        }
        return $chatbot->orderBy('created_at', 'desc')
            ->skip(1)
            ->first();
    }

    public function getIssetChatMessage($message): bool|null
    {
        $query = ChatBot::query();
        if(isset($message['social_network'])){
            $query = $query->where('social_network', $message['social_network']);
        }
        if(isset($message['instance'])){
            $query = $query->where('instance', $message['instance']);
        }
        if(isset($message['contact'])){
            $query = $query->where('contact', $message['contact']);
            $chatbot = clone $query;
        }
        if(isset($message['received_message_text']) && str_word_count(trim($message['received_message_text'])) > 1){
            $chatbot = $chatbot->where('response_message', 'like', '%' . $message['received_message_text'] . '%');
        }

        $chatbot = $chatbot->orderBy('created_at', 'desc')->first();
        Log::info('getIssetChatMessage $chatbot',[$chatbot]);
        try {
            if(!isset($chatbot)){
                Log::info('getIssetChatMessage not isset $chatbot',[true]);
                $currentChatbot = $query->orderBy('created_at', 'desc')->first();
            }else{
                $currentChatbot = $chatbot;
            }
            Log::info('getIssetChatMessage new $currentChatbot',[$currentChatbot]);
            if(!isset($currentChatbot)){
                Log::info('getIssetChatMessage not isset newChatbot',[$currentChatbot]);
                return false;
            }
            Log::info('getIssetChatMessage process');
            $currentChatbot->verify_response = true;
            if($currentChatbot->status == 0){
                Log::info('getIssetChatMessage is status 0',[true]);
                $currentChatbot->status = 1;
            }elseif(isset($message['received_message_text']) && !str_contains(trim($message['received_message_text']), trim($currentChatbot->response_message))){
                Log::info('getIssetChatMessage not is message bot',[true]);
                $currentChatbot->status = 1;
            }
            $currentChatbot->save();
            return true;
        }catch (\Exception $e){
            Log::info('getIssetChatMessage error',[$e->getMessage()]);
            return false;
        }
    }

    public function getInfoInstance($instance): array|bool
    {
        $whatsappConfig = \App\Models\WhatsappConfigAccount::query()
            ->where('instance_id', $instance)
            ->first();

        if(!isset($whatsappConfig->active_messages) || !$whatsappConfig->active_messages){
            return false;
        }
        if((!isset($whatsappConfig->instance_id) || trim($whatsappConfig->instance_id) == "") || (!isset($whatsappConfig->access_token) || trim($whatsappConfig->access_token) == "")){
            return false;
        }
        return $whatsappConfig->toArray();
    }

    public function searchProducts($productName, $productModel, $productBrand, $categories, $vendors, $brands, $accountId, $thread, $withAccount = false): \Illuminate\Support\Collection|null
    {
        $productName = $this->removeConjunctions($productName);
        $productModel = $this->removeConjunctions($productModel);
        $productBrand = $this->removeConjunctions($productBrand);

        $skipKey = [];
        $inAccount = '_not_in_account';
        if($withAccount){
            $inAccount = '_in_account';
        }
        if(Cache::has('skip_key_'.$thread.$inAccount)){
            $skipKey = Cache::get('skip_key_'.$thread.$inAccount);
        }
        $products = DB::connection('mysql_two')
            ->table('products')
            ->select([
                'account_id',
                'product_key',
                'notes',
                'description',
                'price',
                'picture',
                'shopify_product_id',
                'relation_id',
                'brand_id',
                'vendor_id',
                'category_id',
                'qty',
            ])
            ->where(function ($query) use ($productName, $productModel, $productBrand){
                $query->where('notes', 'like', '%' . explode(' ', $productName)[0] . '%');
                foreach (explode('/', $productName) as $valuePm) {
                    $valuePm = trim($valuePm);
                    foreach (explode(' ', $valuePm) as $value) {
                        $value = trim($value);
                        Log::info('consultProduct query product name', [$value]);
                        $query = $query->orWhere('notes', 'like', '%' . $value . '%');
                    }
                }
                foreach (explode(' ', $productModel) as $value) {
                    $value = trim($value);
                    Log::info('consultProduct query product Model', [$value]);
                    $query = $query->orWhere('notes', 'like', '%' . $value . '%');
                    foreach (explode('/', $value) as $val){
                        $val = trim($val);
                        $query = $query->orWhere('notes', 'like', '%' . $val . '%');
                    }
                    foreach (explode('-', $value) as $val){
                        $val = trim($val);
                        $query = $query->orWhere('notes', 'like', '%' . $val . '%');
                    }
                }
                foreach (explode(' ', $productBrand) as $value) {
                    $value = trim($value);
                    Log::info('consultProduct query product Brand', [$value]);
                    $query = $query->orWhere('notes', 'like', '%' . $value . '%');
                }
            })
            ->where(function ($query) use ($categories,$vendors,$brands){
                if(count($categories) > 0){
                    $query->orWhereIn('category_id', $categories);
                }
                if(count($vendors) > 0){
                    $query = $query->orWhereIn('vendor_id', $vendors);
                }
                if(count($vendors) > 0){
                    $query = $query->orWhereIn('brand_id', $brands);
                };
            })
            ->where('qty', '>', 0);
        if($withAccount){
            $products = $products->where('account_id', $accountId);
        }else{
            $products = $products->whereNot('account_id', $accountId);
        }
        if(count($skipKey) > 0){
            $products = $products->whereNotIn('product_key', $skipKey);
        }
        $products = $products->take(4)->get();
        Log::info('searchProducts $products', [$products]);
        $skipKey = array_merge($skipKey,$products->pluck('product_key')->toArray());
        Log::info('searchProducts $skipKey', [$skipKey]);
        Cache::put('skip_key_'.$thread.$inAccount, $skipKey, now()->addMinutes(30));
        return $products;
    }

    public function getAccountName($accountId): string|null {
            return DB::connection('mysql_two')->table('accounts')
                ->select([
                    'name'
                ])
                ->find($accountId)->name ?? null;
    }

    public function getIdsModel($search,$model): array
    {
        $nameId = 'id';
        if(in_array($model,['categories','brands'])){
            if($model === 'categories'){
                $nameId = 'category_id';
            }elseif($model === 'brands'){
                $nameId = 'brand_id';
            }
        }
        $idsModel = [];
        $model = DB::connection('mysql_two')->table($model)
            ->select([
                $nameId,'name'
            ]);

        foreach (explode(" ", $search) as $work){
            $model = $model->where('name', 'like', '%' . $work . '%');
        }
        return $model->pluck($nameId)->toArray();

    }

    public function varSystem($model): array|null
    {
        return DB::connection('mysql_two')->table($model)
            ->select([
                'name',
            ])
            ->get()->toArray();
    }

    public function checkUnansweredMessages(): \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|array|null
    {
        $subQuery = DB::query()
            ->select([
                'cb.*',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY contact ORDER BY created_at DESC) as row_num')
            ])
            ->from('chat_bot as cb')
            ->orderBy('cb.created_at', 'desc')
            ->where('cb.social_network', 0)
            ->where('cb.verify_response', 0)
            ->whereNot('cb.status', 1)
            ->whereNull('cb.deleted_at');

        $subQuerySql = $subQuery->toSql();

        $chatBotQuery = ChatBot::query()
            ->from(DB::raw("($subQuerySql) as sub"))
            ->mergeBindings($subQuery)
            ->where('row_num', '<=', 2)
            ->orderBy('created_at', 'desc')
            ->whereNull('deleted_at')
            ->withTrashed();

        dump($chatBotQuery->toSql());
        return $chatBotQuery->get()->groupBy(['instance','contact']);
    }

    protected function removeConjunctions($text): string
    {
        $conjunctions = [
            "de", "para", "con", "y", "o", "pero", "porque", "a", "e", "u", "o", "ni", "sino", "que", "si",
            "cuando", "como", "donde", "mientras", "aunque", "por", "para", "en", "entre", "hacia", "hasta",
            "desde", "contra", "según", "sin", "sobre", "tras", "durante", "mediante", "bajo", "ante"
        ];
        $words = explode(' ', $text);
        $filteredWords = array_filter($words, function($word) use ($conjunctions) {
            return !in_array(mb_strtolower($word), $conjunctions);
        });
        return implode(' ', $filteredWords);
    }
}
