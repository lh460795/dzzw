<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use App\Models\Tag;
use App\Models\ProjectTag;
use App\Http\Requests\Api\Frontend\TagRequest;
use Illuminate\Support\Facades\Auth;
class TagController extends Controller{

   //项目标识列表
   public function index(Request $request) {
        $per_page = empty($request->input('per_page'))?15:$request->input('per_page');
        $data = Tag::orderBy('timestamp', 'desc')->paginate($per_page);
        return $this->success($data);
   }


    //创建项目标识
    public function create (TagRequest $request) {
       //判断是否有重复记录
       $record = Tag::where('tag', $request->input('tag'))
                            ->get();

       if (collect($record)->isNotEmpty()) {
           return $this->failed('项目标识已存在',400);
       }

       try {

               $data =  [
                   'tag'  => $request->input('tag'),
                   'sort' => empty($request->input('sort'))?0 :$request->input('sort'),
                   'timestamp'    => time(),
               ];

               $tag = Tag::create($data);
               $data['tag'] = $tag->tag;


           //$data['tag'] = $tag->tag;

           return $this->successful($data, '新建成功');
       } catch (\Exception $e) {
           $this->failed('新建失败',400);
       }

    }


    //修改项目标识
    public function update (TagRequest $request){


        $id = $request->input('id');
        if (empty($id)) {
            return $this->failed('id 不能为空',400);
        }


        $record = Tag::where('tag', $request->input('tag'))
            ->where('id', '!=', $id)
            ->get();


        if ($record->isNotEmpty()) {
            return $this->failed('项目标识已存在',400);
        }



        $data =  [
            'tag'  => strip_tags(clean($request->input('tag'), 'user_topic_body')),
        ];

        try {

            Tag::where('id', $id)
                      ->update($data);
            return $this->message('修改成功');

        } catch (\Exception $e) {
            $this->failed('修改失败',400);
        }
    }

    //删除项目标识
    public function delete(Request $request, Tag $tag) {
        $id = $request->input('id');
        if (empty($id)) {
            return $this->failed('id 不能为空',400);
        }

        $flag = $this->idExists($id);
        if ($flag == true) {
            return $this->failed('操作异常', 400);
        }

        try {

            Tag::whereIn('id', $id)
                        ->delete();
            $tag->project()->detach($id);
            return $this->message('删除成功');

        } catch (\Exception $e) {
            $this->failed('删除失败',400);
        }

    }

    private function idExists($id) {

        $record = Tag::find($id);
        if (collect($record)->isEmpty()) {
            return true;
        }
    }


    //清空项目标识
    public function truncate() {
        try {

            Tag::truncate();
            ProjectTag::truncate();
            return $this->message('清空成功');
        } catch (\Exception $e) {
            return $this->failed('清空失败',400);
        }
    }


    //获取标签详情
    public function show(Request $request) {
        $id = $request->input('id');
        if (empty($id)) {
            return $this->failed('id 不能为空',400);
        }

        try {
            $data = Tag::find($id);
            return $this->success($data);
        }catch (\Exception $e) {
            return $this->failed('操作异常',400);
        }
    }

    //获取所有标签
    public function taglist() {
       $data = Tag::orderBy('timestamp', 'desc')->get();
       return $this->success($data);
    }
}