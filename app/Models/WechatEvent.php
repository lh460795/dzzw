<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WechatEvent extends Model
{
    use SoftDeletes;

    protected $table = 'wechat_event';

    public $timestamps = true;
    protected $datas = ['deleted_at'];

    public $events = [
        'mayor_comment' => 1,
        'vice_mayor_comment' => 2,
        'normal_comment' => 3,
        'mayor_reply_comment' => 4,
        'normal_reply_comment' => 5,
        'review_publish' => 6,
        'review_reply' => 7
    ];
}
