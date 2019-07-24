<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;


class LoginOutEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var int 用户id
     */
    protected $user_id;

    /**
     * @var int 退出时间戳
     */
    protected $timestamp;


    /**
     * @var string 登录平台
     */
    protected $platform;

    /**
     * 实例化事件时传递这些信息
     */
    public function __construct($user_id, $timestamp, $platform)
    {
        $this->user_id = $user_id;
        $this->timestamp = $timestamp;
        $this->platform = $platform;
    }

    public function getUser()
    {
        return $this->user_id;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getPlatform()
    {
        return $this->platform;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-default');
    }
}
