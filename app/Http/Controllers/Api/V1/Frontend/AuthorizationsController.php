<?php

namespace App\Http\Controllers\Api\V1\Frontend;
use App\Service\Geetestlib;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Requests\Api\Frontend\AuthorizationRequest;
use App\Http\Controllers\Api\Controller;
use App\Events\LoginEvent;
use App\Events\LoginOutEvent;
use Jenssegers\Agent\Agent;
use App\Http\Requests\Api\Frontend\WeappAuthorizationRequest;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
class AuthorizationsController extends Controller
{
    //端登录
    public function store(AuthorizationRequest $request)
    {
        $username = $request->username;
        $district = $request->district;

        $geetest_challenge = $request->geetest_challenge;
        $geetest_validate = $request->geetest_validate;
        $geetest_seccode = $request->geetest_seccode;
        //验证码
        $onoff = $request->input('onff');
        $captcha_type = $request->input('captcha_type');
        $platform = $request->input('platform');
        if (empty($platform)) {
            $platform = 'pc';
        }

        if ($onoff == 1 && $captcha_type == 1) {
            if (empty($request->captcha_key) || empty($request->captcha_code)) {
                return $this->failed('参数错误',400);
            }

            $captchaData = \Cache::get($request->captcha_key);

            if (!$captchaData) {
                return $this->failed('图片验证码已失效',400);
            }

            if (!hash_equals($captchaData['code'], strtolower($request->captcha_code))) {
                // 验证错误就清除缓存
                \Cache::forget($request->captcha_key);
                return $this->failed('验证码错误',400);
            }
        }

        if ($onoff == 1 && $captcha_type == 2) {
            $this->serverVerrify($geetest_challenge, $geetest_validate, $geetest_seccode);
        }

        if(is_numeric($username)) {
            $credentials['phone'] =  $username;
        } else {
            $credentials['username'] =  $username;
        }


        $credentials['password'] = $request->password;
        $credentials['status'] = 1;
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return $this->failed('用户名密码错误',401);
            //return $this->response->errorUnauthorized(trans('auth.failed'));
        }

        //event(new LoginEvent(Auth::guard('api')->user(), new Agent(), \Request::getClientIp(), time(), $platform));
        //\Log::info('userinfo:',Auth::guard('api')->user()->toArray());
        activitys('frontend')->performedOn(\Auth::guard('api')->user())
            ->withProperties(['type' => '立项消息'])
            ->log(':causer.username 通过'.$platform.'登录前端系统:', '立项消息');

        return $this->respondWithToken($token);
    }

    //极验验证码服务端验证
    private function serverVerrify($geetest_challenge,$geetest_validate,$geetest_seccode) {

        $GtSdk = new GeetestLib($_ENV['GEETEST_ID'], $_ENV['GEETEST_KEY']);
        $gtserver = Cache::get('gtserver');
        $user_id =  Cache::get('user_id');
        if ($gtserver == 1) {
            $result = $GtSdk->success_validate($geetest_challenge, $geetest_validate, $geetest_seccode, $user_id);
            if (!$result) {
                return $this->failed('验证失败', 400);
            }
        } else {
            $result = $GtSdk->fail_validate($geetest_challenge, $geetest_validate, $geetest_seccode);
            if (!$result) {
                return $this->failed('验证失败', 400);
            }
        }
    }


    protected function getFiveYear() {
        $years = [];
        $currentYear = date('Y');
        for ($i=0; $i<5; $i++)
        {
            $years[$i] = $currentYear - $i;
        }


        return $years;
    }



    public function update()
    {
        $token = Auth::guard('api')->refresh();
        return $this->respondWithToken($token);
    }


    //pc 端退出
    public function destroy()
    {
        $id = Auth::guard('api')->id();

        Auth::guard('api')->logout();

        event(new LoginOutEvent($id, time(),'pc'));
        return $this->message('退出成功');
    }

    //微信公众号退出
    public function del()
    {
        Auth::guard('api')->logout();
        return $this->message('退出成功');
    }

    protected function respondWithToken($token)
    {
        $roles = Auth::guard('api')->user()->role()->where('type', 2)->get();
        if (collect($roles)->isNotEmpty()) {
            $roles = $roles->toArray();
            $roleId = array_column($roles,'id');
        } else {
            $roleId = [];
        }

        $role = Auth::guard('api')->user()->roles()->first();
        if (collect($role)->isNotEmpty()) {
            $roleName = $role->name;
        } else {
            $roleName = '';
        }

        $users =  Auth::guard('api')->user();
        $users->role_id = $roleId;
        $users->role_name = $roleName;

        $data = [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'users' => $users,
            'years' => $this->getFiveYear(),
            'current_year' => date('Y')
        ];

        return $this->success($data);
    }



    //小程序登录
    public function weappStore(WeappAuthorizationRequest $request)
    {
        $code = $request->code;

        $miniProgram = \EasyWeChat::miniProgram();
        $data = $miniProgram->auth->session($code);

        // 如果结果错误，说明 code 已过期或不正确，返回 401 错误
        if (isset($data['errcode'])) {
            return $this->failed('code 不正确',400);
        }

        // 找到 openid 对应的用户
        $user = User::where('weapp_openid', $data['openid'])->first();
        $attributes['weixin_session_key'] = $data['session_key'];

        // 未找到对应用户则需要提交用户名密码进行用户绑定
        if (!$user) {
            if (!$request->username) {
                return $this->failed('用户不存在',400);
            }

            $username = $request->username;

            // 用户名可以是邮箱或电话
            filter_var($username, FILTER_VALIDATE_EMAIL) ?
                $credentials['email'] = $username :
                $credentials['phone'] = $username;

            $credentials['password'] = $request->password;

            // 验证用户名和密码是否正确
            if (!Auth::guard('api')->once($credentials)) {
                return $this->failed('用户名或密码错误',400);
            }

            $user = Auth::guard('api')->getUser();
            $attributes['weapp_openid'] = $data['openid'];
        }

        // 更新用户数据
        $user->update($attributes);

        // 为对应用户创建 JWT
        $token = Auth::guard('api')->fromUser($user);

        return $this->respondWithToken($token);
    }

    //验证码api
    public function getGeetest()
    {
        $GtSdk = new GeetestLib(config('services.geetest')['geetest_id'], config('services.geetest')['geetest_key']);

        $data = [
            //'user_id' => @Auth::user()?@Auth::user()->id:'UnLoginUser',
            'user_id' => 1,
            'client_type' => 'web',
            'ip_address' => request()->ip()
        ];

        $status = $GtSdk->pre_process($data);
        Cache::forever('gtserver', $status);
        Cache::forever('user_id', $data['user_id']);
        $data = $GtSdk->get_response_str();

        return $this->success($data);
    }

}