<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\Api\Frontend\AttentionRequest;
use App\Models\Project;
use App\Models\Organization;
use App\Models\Role;
use App\Http\Resources\Api\V1\Frontend\ProjectCollection;
class AttentionController extends Controller{

   //我关注的项目列表
   public function index(Request $request) {
        $per_page = empty($request->input('per_page'))?15:$request->input('per_page');
        $year = $request->input('year')?? null;
        $data = User::find(\Auth::guard('api')->id())->project()
                ->when($year, function ($query) use ($year) {
                    return $query->where('year', $year);
                })
                //->select(['pro_num'])
                ->paginate($per_page);

        $data = new ProjectCollection($data);
        return $this->success($data,'success');
   }

   //我相关的项目列表
   public function lists(Request $request) {
        //五化办、秘书长、常务、市长 可以看全部的项目
       $per_page = empty($request->input('per_page'))?15:$request->input('per_page');
       $year = $request->input('year')?? null;
       $data = Project::when($year, function ($query) use ($year) {
               return $query->where('year', $year);
           })
           ->paginate($per_page);

       $data = new ProjectCollection($data);
       return $this->success($data,'success');
   }


    //关注项目
    public function follow (AttentionRequest $request) {
       $ids = $request->input('ids');

       try {
           User::find(\Auth::guard('api')->id())->project()->attach($ids);
           return $this->message('关注项目成功');
       } catch (\Exception $e) {
           $this->failed('关注项目失败',400);
       }

    }


    //取消关注项目
    public function unfollow (AttentionRequest $request){
        $ids = $request->input('ids');

        try {
            User::find(\Auth::guard('api')->id())->project()->detach($ids);
            return $this->message('取消关注成功');

        } catch (\Exception $e) {
            $this->failed('取消关注失败',400);
        }
    }

    public function test(){
        $data = [
            'uid' => \Auth::id(),
            'pname' => '水电项目',
            'lx_corp' => '孝感市发改委',
            'corp_id' => 1,
        ];

        Project::create($data);


    }

    public function tet() {
        $data = Organization::with('user')->orderBy('level', 'asc')->get()->toArray();
        dd($data);
    }


}