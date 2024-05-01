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
        'media_file'
    ];

    protected $table = 'chat_bot';

    protected $status_reference = [
        0 => 'received',
        1 => 'answered',
        2 => 'pending',
        3 => 'read'
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
}
