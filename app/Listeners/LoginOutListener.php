<?php

namespace App\Listeners;

use App\Events\LoginOutEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\UserLive;
class LoginOutListener implements ShouldQueue
{
    //public $tries = 1;
    // handle方法中处理事件
    public function handle(LoginOutEvent $event)
    {
        //获取事件中保存的信息
        $id = $event->getUser();
        $timestamp = $event->getTimestamp();
        $platform = $event->getPlatform();
        //登录之后更新用户在线状态 如有用户已经存在更新时间和在线状态就好,否则插入
        UserLive::where('user_id', $id)
            ->where('platform', $platform)
            ->update(['is_live'=>0, 'updatetime'=> $timestamp]);
    }
}
