<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\Api\Frontend\CenterRequest;
use App\Models\Project;
use App\Service\PendingService;
use App\Service\TodoService;
use App\Models\Role;
class PendingController extends Controller{

   protected $pendingService;
   protected $todoService;

   public function __construct(PendingService $pendingService, TodoService $todoService)
   {
       $this->pendingService = $pendingService;
       $this->todoService = $todoService;
   }

    //待办事务数量
   public function index() {
        $data = $this->pendingService->getMarkData();
        return $this->success($data);
   }

   public function detail() {
       $data = $this->todoService->getMarkData();
       return $this->success($data);
   }


    //待办事务详情
    public function editpassword (CenterRequest $request) {
        $user = \Auth::guard('api')->user();

        $auth = \Auth::guard('api')->once([
            'username' => $user->username,
            'password' => $request->input('old_password'),
        ]);

        if (! $auth) {
            return $this->failed('原密码不正确',400);
        }

        $password = app('hash')->make($request->input('password'));

        try {
            $user->update(['password' => $password]);
            return $this->message('修改成功');
        } catch (\Exception $e) {
            return $this->failed('修改失败');
        }
    }


}