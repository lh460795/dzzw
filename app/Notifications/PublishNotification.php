<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Yansongda\LaravelNotificationWechat\WechatChannel;
use Yansongda\LaravelNotificationWechat\WechatMessage;

class PublishNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $model;
    protected $template;
    protected $type;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($model,$template,$type)
    {
        $this->model = $model;
        $this->template = $template;
        $this->type = $type;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database',WechatChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    /*
     * 微信推送
     */
    public function toWechat($notifiable)
    {
        return WechatMessage::create()
            ->to($notifiable->weixin_openid)
            ->template("2H1lBY_oLkNuz-dHb1cOGltpl5CYlVCXmArK4a_yYg0")
            //TODO url
            ->url('http://github.com/yansongda')
            ->data($this->template);
    }

    /*
     * 消息入库
     */
    public function toDatabase($notifiable)
    {
        return [
            'type' => \DB::table('wechat_event')->where('id', $this->type)->value('event_name'),
            'type_id' => $this->type,
            'content' => $this->model->content,
        ];
    }
}
