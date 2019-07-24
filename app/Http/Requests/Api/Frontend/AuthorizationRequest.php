<?php

namespace App\Http\Requests\Api\Frontend;
use App\Http\Requests\Api\FormRequest;

class AuthorizationRequest extends FormRequest
{

    public function rules()
    {
        return [
            'username' => 'required|string',
            'password' => 'required|string|min:6',
            'distict' => '',
            'captcha_key' => 'string',
            'captcha_code' => 'string',
            'onoff' => 'required',
            'platform' => '',
        ];
    }

    public function attributes()
    {
        return [
            'username' => '用户名',
            'password' => '密码',
            'captcha_key' => '图片验证码 key',
            'captcha_code' => '图片验证码',
        ];
    }

    public function messages()
    {
        return [
            'username.required' => '用户名 不能为空',
            'password.array' => '密码 不能为空',
        ];
    }
}