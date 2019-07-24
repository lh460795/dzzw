<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Type;
use App\Http\Resources\Api\V1\Frontend\MarkResource;
use App\Http\Resources\Api\V1\Frontend\MarkCollection;

class MarkController extends Controller{

   //项目列表
   public function index(Request $request) {
       $per_page = empty($request->input('per_page'))?15:$request->input('per_page');
       $data = Project::with('tag')->filter($request->all())->orderBy('created_at', 'desc')->paginate($per_page);
       $data = new MarkCollection($data);
       return $this->success($data);
   }


    //标识项目
    public function mark (Request $request,Project $project) {
        $ids = $request->input('ids');
//        if (empty($ids)) {
//            return $this->failed('参数错误', 400);
//        }

        $id = $request->input('id');
        if (empty($id)) {
            return $this->failed('参数错误', 400);
        }

        try {
            Project::findOrFail($id)->tag()->sync($ids);
            return $this->message('标识项目成功');
        } catch (\Exception $e) {
            $this->failed('标识项目失败',400);
        }
    }

    //项目类型列表
    public function typelist() {
       $data = Type::get();
       return $this->success($data);
    }


}