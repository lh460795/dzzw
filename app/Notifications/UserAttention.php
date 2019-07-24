<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Project;
class UserAttention extends Notification
{
    use Queueable;
    public $project;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
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

    public function toDatabase($notifiable)
    {
        $user = $this->project->users;

        // 存入数据库里的数据
        return [
            'pname' => $this->project->pname,
            'user_id' => $this->project->uid,
            'user_name' => $user->username,
            'type' => '立项消息',
            'type_id' => \DB::table('msg_type')->where('msg_type', '立项消息')->value('id'),
            'department' => $this->project->lx_corp,
            'department_id' => $this->project->corp_id,
            'content' => $this->project->lx_corp. '申报了'.$this->project->pname. '项目,需要您审核，点击此条消息立即操作',
        ];
    }
}
