<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use App\Models\CusMessage;
use App\Http\Requests\Api\Frontend\CusMessageRequest;
use Illuminate\Support\Facades\Auth;
class CusMessageController extends Controller{

   //常用语列表
   public function index(Request $request) {
       $per_page = empty($request->input('per_page'))?15:$request->input('per_page');
        $data = CusMessage::where('user_id', \Auth::guard('api')->id())
                ->orderBy('time', 'desc')
                ->paginate($per_page);
        return $this->success($data);
   }


    //创建常用语
    public function create (CusMessageRequest $request) {
       //判断是否有重复记录
       $record = CusMessage::where('user_id', \Auth::guard('api')->id())
                            ->where('message', $request->input('message'))
                            ->get();

       if (collect($record)->isNotEmpty()) {
           return $this->failed('常用语已存在',400);
       }

       try {

           $data =  [
               'message'  => $request->input('message'),
               'user_id'  => \Auth::guard('api')->id(),
               'username' =>  \Auth::guard('api')->user()->username,
               'time'    => time()
           ];

           $cusMessage = CusMessage::create($data);
           $data['message'] = $cusMessage->message;

           return $this->successful($data, '新建成功');
       } catch (\Exception $e) {
           return $this->failed('新建失败',400);
       }

    }


    //修改常用语
    public function update (CusMessageRequest $request){


        $id = $request->input('id');
        if (empty($id)) {
            return $this->failed('id 不能为空',400);
        }

        $model = CusMessage::find($id);
        $this->authorize('update', $model);

        $record = CusMessage::where('user_id', \Auth::guard('api')->id())
            ->where('message', $request->input('message'))
            ->where('id', '!=', $id)
            ->get();

        if (collect($record)->isNotEmpty()) {
            $this->failed('常用语已存在',400);
        }

        $flag = $this->idExists($id);
        if ($flag == true) {
            return $this->failed('操作异常', 400);
        }

        $data =  [
            'message'  => strip_tags(clean($request->input('message'), 'user_topic_body')),
        ];

        try {

            CusMessage::where('id', $id)
                      ->update($data);
            return $this->message('修改成功');

        } catch (\Exception $e) {
            $this->failed('修改失败',400);
        }
    }

    //删除常用语
    public function delete(Request $request) {
        $id = $request->input('id');
        if (empty($id)) {
            return $this->failed('id 不能为空',400);
        }

        $id = json_decode($id);


        $flag = $this->idExists($id);
        if ($flag == true) {
            return $this->failed('操作异常', 400);
        }

        try {

            CusMessage::whereIn('id', $id)
                      ->where('user_id', \Auth::guard('api')->id())
                      ->delete();
            return $this->message('删除成功');

        } catch (\Exception $e) {
            $this->failed('删除失败',400);
        }

    }

    private function idExists($id) {

        $record = CusMessage::find($id);
        if (collect($record)->isEmpty()) {
            return true;
        }
    }

    public function truncate() {
        try {

            CusMessage::where('user_id', \Auth::guard('api')->id())->delete();

            return $this->message('清空成功');
        } catch (\Exception $e) {
            return $this->failed('清空失败',400);
        }
    }
}