<?php

namespace App\Listeners;

use App\Events\LoginEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\UserLive;
class LoginListener
{
    //public $tries = 1;
    // handle方法中处理事件
    public function handle(LoginEvent $event)
    {

        //获取事件中保存的信息
        $user = $event->getUser();
        $agent = $event->getAgent();
        $ip = $event->getIp();
        $timestamp = $event->getTimestamp();
        $platform = $event->getPlatform();
        //登录信息
        $login_info = [
            'login_ip' => $ip,
            'login_time' => $timestamp,
            'user_id' => $user->id,
            'user_name' => $user->username,
            'area_id' => $user->area_id,
            'area' => $user->area,
            'units_id' => $user->units_id,
            'units' => $user->units,
            'phone' => $user->phone,
        ];

        $addresses = \Ip::find($ip);
        $login_info['login_address'] = implode(' ', $addresses);

        // jenssegers/agent 的方法来提取agent信息
        $login_info['device'] = $agent->device(); //设备名称
        $browser = $agent->browser();
        $login_info['browser'] = $browser . ' ' . $agent->version($browser); //浏览器
        //$platform = $agent->platform();
        $login_info['platform'] = $platform; //平台
        $login_info['language'] = implode(',', $agent->languages()); //语言
        //设备类型
        if ($agent->isTablet()) {
            // 平板
            $login_info['device_type'] = 'tablet';
        } else if ($agent->isMobile()) {
            // 便捷设备
            $login_info['device_type'] = 'mobile';
        } else if ($agent->isRobot()) {
            // 爬虫机器人
            $login_info['device_type'] = 'robot';
            $login_info['device'] = $agent->robot(); //机器人名称
        } else {
            // 桌面设备
            $login_info['device_type'] = 'desktop';
        }


        //根据url 判断插入的数据库
        $uri = request()->path();
        $pattern = '/^api\/frontend/';
        $flag = preg_match($pattern, $uri);

        $data = [
            'last_login_time' => time(),
            'last_login_ip'   => request()->ip(),
        ];


        //登录之后更新用户在线状态 如有用户已经存在更新时间和在线状态就好,否则插入
        $record = UserLive::where('user_id', \Auth::guard('api')->id())
                          ->where('platform', $platform)->get();

        if (collect($record)->isNotEmpty()) {
            UserLive::where('user_id', \Auth::guard('api')->id())
                ->where('platform', $platform)->update(['is_live'=>1, 'updatetime'=> time(), 'ip'=>\Request::getClientIp()]);
        }

//        else {
//
//            $data_info = [
//                'updatetime' => $timestamp,
//                'user_id' => $user->id,
//                'username' => $user->username,
//                'district_id' => $user->area_id,
//                'platform' => $platform,
//                'units_id' => $user->units_id,
//                'phone' => $user->phone,
//                'is_live' => 1
//            ];
//
//            UserLive::create($data_info);
//        }

        if ($flag) {
            //更新用户表登录时间和登录ip
            DB::table('users')->where('id', \Auth::guard('api')->id())->update($data);
            DB::table('login_log')->insert($login_info);
        } else {
            DB::table('admin_users')->where('id', \Auth::guard('api')->id())->update($data);
            DB::table('admin_login_log')->insert($login_info);
        }


    }
}
