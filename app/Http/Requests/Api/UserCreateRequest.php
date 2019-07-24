<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\FormRequest;

class UserCreateRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|unique:admin_users|email',
            'phone'   => 'required|numeric|regex:/^1[3456789][0-9]{9}$/|unique:admin_users',
            'username'  => 'required|min:4|max:14|unique:admin_users',
            'password'  => 'required|min:6|max:14',
            'real_name' => 'required',
            'corp_id' => 'required',
            'corp_name' => 'required',
        ];
    }

    public function attributes()
    {
        return [
            'email' => '邮箱',
            'phone' => '手机号',
            'username' => '用户名',
            'password' => '密码',
        ];
    }

    public function messages()
    {
        return [


        ];
    }
}
