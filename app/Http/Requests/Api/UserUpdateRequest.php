<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\FormRequest;

class UserUpdateRequest extends FormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $reture = [
            'id'=> 'required|integer',
            'email' => 'unique:admin_users,email,'.$this->input('id').',id|email',
            'phone' => 'numeric|regex:/^1[34578][0-9]{9}$/|unique:admin_users,phone,'.$this->input('id').',id',
            'username'  => 'min:4|max:14|unique:users,username,'.$this->input('id').',id',
        ];

        if ($this->input('password') ){
            $reture['password'] = 'min:6|max:14';
        }

        return $reture;
    }

    public function attributes()
    {
        return [
            'email' => '邮箱',
            'phone' => '手机号',
            'username' => '用户名',
            'password' => '密码',
            'password_confirmation' => '确认密码'
        ];
    }

    public function messages()
    {
        return [

        ];
    }
}
