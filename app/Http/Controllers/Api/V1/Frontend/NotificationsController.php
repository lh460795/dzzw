<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Frontend\NoticationRequest;
use App\Models\Notifications;
use App\Models\MsgType;
class NotificationsController extends Controller
{
    //消息列表
    public function index(Request $request, Notifications $notifications)
    {
        $per_page = empty($request->input('per_page'))?15:$request->input('per_page');
        $notifications = $notifications
            ->where('notifiable_id', \Auth::guard('api')->id())
            ->select('relate_id','type','content','created_at','status','department_id')
            ->filter($request->all())->paginate($per_page);
        return $this->success($notifications);
    }

    //标记为已读
    public function mark(NoticationRequest $request) {

        $ids = $request->input('id');

        try {
            Notifications::whereIn('relate_id', $ids)
                ->where('notifiable_id', \Auth::guard('api')->id())
                ->update(['status'=>1]);
            return $this->message('标记成功');
        } catch (\Exception $e) {
            return $this->failed('标记失败',400);
        }
    }

    //删除信息
    public function delete(Request $request) {
        $ids = $request->input('id');

        try {
            Notifications::where('notifiable_id', \Auth::guard('api')->id())
                        ->whereIn('relate_id', $ids)->delete();
            return $this->message('删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败',400);
        }
    }

    //当前登录用户未读消息数量
    public function unread(Request $request) {
        $data =  ['unread_count' => \Auth::guard('api')->user()->notification_count];
        return $this->success($data);
    }

    //一键清空消息
    public function truncate() {
        try {
            \Auth::guard('api')->user()->notifications()->delete();
            return $this->message('清空成功');
        } catch (\Exception $e) {
            return $this->failed('清空失败',400);
        }
    }

    //获取站内信详情
    public function show(Request $request) {
        $id = $request->input('id');

        if (empty($id)) {
            return $this->failed('id 不能为空');
        }

        try {
            $data = Notifications::find($id);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->failed('操作异常');
        }
    }

    //站内信类型
    public function noticelist() {
        $data = MsgType::get();
        return $this->success($data);
    }
}
