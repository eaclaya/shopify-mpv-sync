<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatBot extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'social_network',
        'instance',
        'contact',
        'status',
        'message_type',
        'message_id',
        'referenced_message_id',
        'received_message_text',
        'response_message',
        'media_file',
        'thread',
        'verify_response'
    ];

    protected $table = 'chat_bot';

    protected $status_reference = [
        0 => 'received',
        1 => 'answered_by_user',
        2 => 'answered_by_chatbot',
        3 => 'finished',
        4 => 'in_process',
    ];

    protected $message_type_reference = [
        0 => 'conversation',
        1 => 'imageMessage',
        2 => 'documentMessage',
        3 => 'audioMessage',
        4 => 'videoMessage',
        5 => 'otherMessage',
    ];

    protected $social_network_reference = [
        0 => 'whatsapp',
        1 => 'telegram',
        2 => 'facebook',
        3 => 'instagram',
        4 => 'twitter',
    ];

    public function verifyActive(): bool
    {

        $creationTime = $this->created_at;
        $currentTime = now();
        $differenceInSeconds = $creationTime->diffInSeconds($currentTime);
        if($differenceInSeconds < 40){
            return false;
        }
        dump($this);
        if(!isset($this->response_message) || trim($this->response_message) === ''){
            dump('no response');
            return false;
        }
        if($this->verify_response === 1){
            dump('verified');
            return false;
        }
        if(!in_array($this->status,[2,4])){
            dump('not in status');
            return false;
        }
        return true;
    }
}
