<?php

namespace App\Http\Controllers\Api\V1\Backend;


use App\Models\Comment;
use App\Models\Reply;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $model = Comment::with(['reply']);
        if ($request->get('name')){
            $model = $model->where('user_name','like','%'.$request->get('name').'%');
        }
        $res = $model->orderBy('created_at','desc')->paginate($request->get('limit',30))->toArray();
        return $this->respond($res);
    }

    public function reply(Request $request)
    {
        if($request->get('model') == 'comment') {
            $where = ['reply_id' => $request->get('id')];
        }else{
            $where = ['reply_id' => 0,'parent_id' => $request->get('id')];
        }
        $model = Reply::with(['children','project'])
            ->where($where);
        $res = $model->orderBy('created_at','desc')
            ->paginate($request->get('limit',30))
            ->toArray();

        return $this->respond($res);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request,$id)
    {
        if($request->get('model') == 'comment') {
            $comment = Comment::find($id);
        }else{
            $comment = Reply::find($id);
        }
        return $this->respond($comment);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if($request->get('model') == 'comment') {
            $comment = Comment::findOrFail($id);
        }else{
            $comment = Reply::findOrFail($id);
        }
        $data = $request->only('content');

        if ($comment->update($data)){
            return $this->success($comment);
        }else{
            return $this->failed('操作失败');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $ids = $request->get('ids');
        if (empty($ids)){
            return $this->failed('请选择删除项');
        }
        if($request->get('model') == 'comment') {
            if (Comment::destroy($ids)){
                return $this->success([]);
            }
        }else{
            if (Reply::destroy($ids)){
                return $this->success([]);
            }
        }
        return $this->failed('操作失败');
    }
}
