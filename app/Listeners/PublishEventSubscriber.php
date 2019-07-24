<?php

namespace App\Listeners;

use App\Models\NotificationRange;
use App\Models\Project;
use App\Models\Reply;
use App\Models\Review;
use App\Models\Template;
use App\Models\User;
use App\Models\WechatEvent;
use App\Notifications\InvoicePaid;
use App\Notifications\PublishNotification;
use App\Work\Model\FlowProcess;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\Role;
use App\Models\Comment;


class PublishEventSubscriber
{
    public $msg_type;

    /**
     * 发布事件。
     */
    public function onPublish($event)
    {

//        //获取发送范围
//        $wechat_event = WechatEvent::find($event->type);
//
//        $ranges = explode(',',$wechat_event->range);
//
//        foreach ($ranges as $key => $value)
//        {
//            $range = NotificationRange::find($value);
//
//            $describes = explode(',',$range->describe);
//
//            foreach ($describes as $describe)
//            {
//                //获取发送对象
//                $users = $this->getUsers($describe,$event);
//
//                //组装消息模板
//                $template = $this->getTemplate($event,$range);
//
//                //组装url
//                $url = $this->generateUrl($event,$range);
//
//                Notification::send($users,new PublishNotification($event->model,$template,$event->type));
//            }
//        }

    }

    /**
     * 生成url
     * @param $event
     * @param $range
     */
    public function generateUrl($event,$range)
    {

    }


    /**
     * 获取模板
     * @param $event
     * @param $range
     * @return array
     */

    public function getTemplate($event,$range)
    {
        $template = Template::find($range->template_id);

        $title = $this->getTitle($event,$range,$template);

        $remark = $this->getRemark($event,$range,$template);

        $data = [
            'first' => $template->first,
            'keyword1' => $title,
            'keyword2' => '操作成功',
            'keyword3' => date('Y-m-d H:i:s'),
            'remark' => $remark
        ];

        return $data;
    }


    /**
     * 模板remark
     * @param $event
     * @param $range
     * @param $template
     * @return mixed
     */
    public function getRemark($event,$range,$template)
    {
        //前7为点评事件
        if($range->id <= 7) {
            $project = Project::find($event->model->pid);
        }

        //TODO 单位一把手
        $unit_leader = Auth::user()->username;

        $remark = str_replace('{$content}',$event->model->content,str_replace('{$unit_leader}',$unit_leader,$template->content));

        return $remark;
    }

    /**
     * 模板标题
     * @param $event
     * @param $range
     * @param $template
     * @return mixed
     */
    public function getTitle($event,$range,$template)
    {
        $user = Auth::user();

        //判断用户是否属于市长、副市长、常务副市长、秘书长 职位+姓名，其他 单位+姓名
        $role = $user->roles()->first();
        $role_level  = Role::where(['name' => '秘书长'])->get(['level_id'])->first();
        if($role->level_id >= $role_level->level_id) {
            $duty = $role->display_name;
        }else{
            $duty = $user->units;
        }

        //前7为点评事件
        if($range->id <= 7) {
            $project = Project::find($event->model->pid);
        }


        switch ($range->id)
        {
            case 1:
                $title = str_replace('{$project_name}',$project->pname,str_replace('{$author}',$user->username,$template->title));
                break;
            case 2:
                $title = str_replace('{$project_name}',$project->pname,str_replace('{$author}',$user->username,$template->title));
                break;
            case 3:
                $title = str_replace('{$duty}',$duty,str_replace('{$project_name}',$project->pname,str_replace('{$author}',$user->username,$template->title)));
                break;
            case 4:
                $title = str_replace('{$duty}',$duty,str_replace('{$project_name}',$project->pname,str_replace('{$author}',$user->username,$template->title)));
                break;
            case 5:
                $title = str_replace('{$duty}',$duty,str_replace('{$project_name}',$project->pname,str_replace('{$author}',$user->username,$template->title)));
                break;
            case 6:
                $model = $event->model;
                if($model->reply_id) {
                    $comment = Comment::find($model->reply_id);
                }else{
                    $reply = Reply::find($model->parent_id);
                    $comment = Comment::find($reply->reply_id);
                }
                $comment_author = $comment->user;

                $title = str_replace('{$project_name}',$project->pname,str_replace('{$author}',$user->username,$template->title));
                $title = str_replace('{$comment_author}',$comment_author->name,str_replace('{$comment_author_duty}',$duty,$title));
                break;
            case 7:
                $title = str_replace('{$duty}',$duty,str_replace('{$project_name}',$project->pname,str_replace('{$author}',$user->username,$template->title)));
                break;
            case 8:
                $title = str_replace('{$duty}',$duty,str_replace('{$author}',$user->username,$template->title));
                break;
            case 9:
                $model = $event->model;
                if($model->reply_id) {
                    $review = Review::find($model->reply_id);
                }else{
                    $reply = Reply::find($model->parent_id);
                    $review = Review::find($reply->reply_id);
                }
                $review_author = $review->user;

                if($review->title) {
                    $content = $review->title;
                }else{
                    $content = mb_substr(0,15,$review->content);
                }

                $title = str_replace('{$author}',$user->username,$template->title);
                $title = str_replace('{$review_author}',$review_author->name,str_replace('{$review_content}',$content,$title));

                break;
        }

        return $title;
    }

    /**
     * 获取发送对象
     * @param $describe
     * @param $event
     * @return \Illuminate\Support\Collection|mixed
     */
    public function getUsers($describe,$event)
    {
        switch ($describe)
        {
            //主办单位人员
            case 'unit_users':

                $users = $this->getUnitUsers($event);

                break;
            //除主办单位外其他人员
            case 'users_except_unit':

                $unit_users = $this->getUnitUsers($event)->toArray();

                $unit_ids = array_column($unit_users,'id');

                $users = User::whereNotNull('weixin_openid')->whereNotIn('id',$unit_ids)->get();

                break;
            //项目审核人员
            case 'project_check_users':

                $users = $this->getProjectCheckUsers($event);

                break;
            //全平台用户
            case 'all_platform':

                $users = User::whereNotNull('weixin_openid')->get();

                break;
            //被回复用户
            case 'author':

                $users = $this->getAuthor($event);

                break;
        }



        foreach ($users as $key => $user)
        {
            if($user->id == \Illuminate\Support\Facades\Auth::id()){
                unset($users[$key]);
            }
        }

        return $users;
    }

    /**
     * 获取主办单位人员
     * @param $event
     * @return mixed
     */
    public function getUnitUsers($event)
    {
        $pid = $event->model->pid;

        $units_id = Project::find($pid)->units_id;

        $users = User::where(['units_id' => $units_id])->get();

        return $users;
    }

    /**
     * 获取被回复对象
     * @param $event
     */
    public function getAuthor($event)
    {
        //区分点评述评和回复等级
        if($event->model->reply_id) {

            switch ($event->model->type)
            {
                case 1:

                    $model = Comment::find($event->model->reply_id);

                    break;
                case 2:

                    $model = Review::find($event->model->reply_id);

                    break;
            }

        }else{

            $model = Reply::find($event->model->to_id);

        }

        $user  = User::where(['id' => $model->user_id])->get();

        return $user;
    }

    /**
     * 获取项目审核人员
     * @param $event
     */
    public function getProjectCheckUsers($event)
    {
        $project = Project::find($event->model->pid);

        $user_ids = FlowProcess::where(['flow_id' => $project->wf_id])->get(['auto_sponsor_ids'])->toArray();

        $users = User::whereIn('id',array_column($user_ids,'auto_sponsor_ids'))->where('units_id','<>',$project->units_id)->get();

        return $users;
    }

    /**
     * 为订阅者注册监听器。
     *
     * @param  Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            'App\Events\PublishEvent',
            'App\Listeners\PublishEventSubscriber@onPublish'
        );
    }

}
