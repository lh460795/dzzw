<?php

namespace App\Observers;
use App\Models\CusMessage;
use App\Models\User;

class MessageObservers {

    public function creating(CusMessage $cusMessage)
    {
        // XSS 过滤
        $cusMessage->message = clean($cusMessage->message, 'default');
        $cusMessage->message = strip_tags($cusMessage->message);
    }


    public function saving(CusMessage $cusMessage)
    {
        // XSS 过滤
        $cusMessage->message = clean($cusMessage->message, 'default');
    }

    public function updating(CusMessage $cusMessage)
    {
        // XSS 过滤
        $cusMessage->message = clean($cusMessage->message, 'user_topic_body');
    }
}