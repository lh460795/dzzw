<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Events\PublishEvent;
use App\Http\Requests\ReplyRequest;
use App\Models\Reply;
use App\Models\Review;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;

class ReplyController extends Controller
{

    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /*
     * 发布回复
     */
    public function store(ReplyRequest $request)
    {
        $data = $request->all();
        $model = Reply::create($data);
        if($model) {
            //对于多级回复
            if($model->reply_id == 0) {
                $model->to_reply = $model->toReply;
            }
            $model->user = $model->user;
            $model->user->corp = $model->user->corp;
            return $this->success($model);
        }else{
            return $this->failed('操作失败');
        }
    }

    /*
     * 删除（连带删除回复）
     */
    public function delete()
    {
        $id = $this->request->id;
        $this->authorize('delete',Reply::find($id));
        if(Reply::destroy($id)) {
            return $this->success([]);
        }else{
            return $this->failed('操作失败');
        }
    }
}
