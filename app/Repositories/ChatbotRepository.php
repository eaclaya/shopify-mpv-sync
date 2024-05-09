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
        $message_id = Arr::get($chat, 'message_id') ?? Arr::get($chat, 'referenced_message_id');
        $chatbot = ChatBot::query()->where('message_id', $message_id)->first();
        $chatbot->status = Arr::get($data, 'status');
        $chatbot->response_message .= Arr::get($data, 'response_message') ? Arr::get($data, 'response_message').' *|* ' : '';
        $chatbot->save();

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
            $chatbot = $query = $query->where('contact', $message['contact']);
        }
        if(isset($message['received_message_text']) && str_word_count(trim($message['received_message_text'])) > 1){
            $chatbot = $chatbot->where('response_message', 'like', '%' . $message['received_message_text'] . '%');
        }
        Log::info('getIssetChatMessage $chatbot query', [
            $chatbot->toSql(),
            $chatbot->getBindings()
        ]);

        $chatbot = $chatbot->orderBy('created_at', 'desc')->first();
        Log::info('getIssetChatMessage $chatbot',[$chatbot]);
        try {
            if(!isset($chatbot)){
                Log::info('getIssetChatMessage isset $chatbot',[true]);
                $chatbot = $query->orderBy('created_at', 'desc')->first();
            }
            if(!isset($chatbot)){
                return false;
            }
            $chatbot->verify_response = true;
            if($chatbot->status == 0){
                Log::info('getIssetChatMessage is status 0',[true]);
                $chatbot->status == 1;
            }
            if(isset($message['received_message_text']) && !str_contains($chatbot->received_message_text, $message['received_message_text'])){
                Log::info('getIssetChatMessage not is message bot',[true]);
                $chatbot->status == 1;
            }
            $chatbot->save();
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
                    foreach (explode(' ', $valuePm) as $value) {
                        Log::info('consultProduct query product name', [$value]);
                        $query = $query->where('notes', 'like', '%' . $value . '%');
                    }
                }
                foreach (explode(' ', $productModel) as $value) {
                    Log::info('consultProduct query product Model', [$value]);
                    $query = $query->where('notes', 'like', '%' . $value . '%');
                    foreach (explode('/', $value) as $val){
                        $query = $query->where('notes', 'like', '%' . $val . '%');
                    }
                    foreach (explode('-', $value) as $val){
                        $query = $query->where('notes', 'like', '%' . $val . '%');
                    }
                }
                foreach (explode(' ', $productBrand) as $value) {
                    Log::info('consultProduct query product Brand', [$value]);
                    $query = $query->where('notes', 'like', '%' . $value . '%');
                }
            })
            ->where(function ($query) use ($categories,$vendors,$brands){
                if(count($categories) > 0){
                    $query->whereIn('category_id', $categories);
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
        $skipKey = array_merge($skipKey,$products->pluck('product_key')->toArray());
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
}