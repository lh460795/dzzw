<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\Api\Frontend\CenterRequest;
use App\Models\Project;
class CenterController extends Controller{

   //用户详情信息
   public function index() {
        $data = User::select('username', 'real_name','phone', 'email', 'status')
            ->find(\Auth::guard('api')->id());
        return $this->success($data,'success');
   }


    //修改密码
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


    //修改用户信息
    public function editinfo (Request $request){

        $phone = $request->input('phone');
        $email = $request->input('email');

        if (empty($phone)) {
            $data = [
                'email' => $email
            ];
        }

        if (empty($email)) {
            $data = [

                'phone' => $phone
            ];
        }

        if (!empty($phone) && !empty($email)) {
            $data = [
                'phone' => $phone,
                'email' => $email
            ];
        }

        $record = User::where('id', '!=', \Auth::guard('api')->id())
                       ->where('phone', $phone)
                       ->first();

        if (collect($record)->isNotEmpty()) {
            return $this->failed('手机号已经存在');
        }

        $record1 = User::where('id', '!=', \Auth::guard('api')->id())
            ->where('email', $email)
            ->first();

        if (collect($record1)->isNotEmpty()) {
            return $this->failed('邮箱已经存在');
        }

        try {
            \Auth::guard('api')->user()->update($data);
            return $this->message('编辑资料成功');
        } catch (\Exception $e) {
            return $this->failed('编辑资料失败',400);
        }
    }


}