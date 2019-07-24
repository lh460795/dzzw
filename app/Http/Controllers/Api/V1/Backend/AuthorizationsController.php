<?php

namespace App\Http\Controllers\Api\V1\Backend;
use Auth;
use Illuminate\Http\Request;
use App\Http\Requests\Api\AuthorizationRequest;
use App\Http\Controllers\Api\Controller;
use App\Events\LoginEvent;
use Jenssegers\Agent\Agent;
class AuthorizationsController extends Controller
{
    public function store(AuthorizationRequest $request)
    {
        $username = $request->username;
        $disname = $request->disname;
        $platform = $request->input('platform');
        if (empty($platform)) {
            $platform = 'pc';
        }
        $credentials['username'] =  $username;
        $credentials['password'] = $request->password;
        if (!$token = Auth::guard('admins')->attempt($credentials)) {
            return $this->response->errorUnauthorized(trans('auth.failed'));
        }


<<<<<<< .mine
        //event(new LoginEvent(Auth::guard('admins')->user(), new Agent(), \Request::getClientIp(), time()));
||||||| .r11039
        event(new LoginEvent(Auth::guard('admins')->user(), new Agent(), \Request::getClientIp(), time()));
=======
        event(new LoginEvent(Auth::guard('admins')->user(), new Agent(), \Request::getClientIp(), time(), $platform));
>>>>>>> .r11060

        return $this->respondWithToken($token)->setStatusCode(201);
    }

    public function update()
    {
        $token = Auth::guard('admins')->refresh();
        return $this->respondWithToken($token);
    }

    public function destroy()
    {
        Auth::guard('admins')->logout();
        return $this->response->noContent();
    }

    protected function respondWithToken($token)
    {
        return $this->response->array([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => \Auth::guard('admins')->factory()->getTTL() * 60
        ]);
    }

}