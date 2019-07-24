<?php
/**
 * Created by PhpStorm.
 * User: berts
 * Date: 2019/6/2
 * Time: 20:38
 */

namespace App\Listeners;

use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Models\Notifications;
class LogNotification implements ShouldQueue{
    public function handle(NotificationSent $event)
    {

        $notication = Notifications::find($event->notification->id);
        $data = json_decode($notication->data);
        $type = $data->type;
        $type_id = $data->type_id;
        $department = $data->department ?? 0;
        $department_id = $data->department_id ?? 0;

        $notication->type = $type;
        $notication->type_id = $type_id;
        $notication->department = $department;
        $notication->department_id = $department_id;
        $notication->content = $data->content;
        $notication->relate_id = $event->notification->id;
        $notication->save();
    }
}
