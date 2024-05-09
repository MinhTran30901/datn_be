<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageSent implements ShouldBroadcast
{
    public $sender_id;

    public $receiver_id;

    public $content;

    public $image_url;

    public function __construct($sender_id, $receiver_id, $image_url, $content)
    {
        $this->sender_id = $sender_id;
        $this->receiver_id = $receiver_id;
        $this->content = $content;
        $this->image_url = $image_url;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.'.$this->receiver_id);
    }
}
