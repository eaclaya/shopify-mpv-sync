<?php
namespace App\Repositories;

use App\Models\ChatBot;
use Illuminate\Support\Arr;

class ChatbotRepository
{
    public function save($message): void{
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

    public function update($message): \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null {
        $message_id = Arr::get($message, 'message_id') ?? Arr::get($message, 'referenced_message_id');
        $chatbot = ChatBot::query()->where('message_id', $message_id);
        $chatbot->status = Arr::get($message, 'status');
        $chatbot->response_message .= Arr::get($message, 'response_message') ?? '';
        $chatbot->save();

        return $chatbot;
    }
}
