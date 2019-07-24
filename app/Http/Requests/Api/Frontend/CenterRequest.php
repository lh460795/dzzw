<?php

namespace App\Http\Requests\Api\Frontend;

use App\Http\Requests\Api\FormRequest;
class CenterRequest extends FormRequest
{


    public function rules()
    {
        return [
            'old_password' => 'required',
            'password' => 'required|confirmed|different:old_password',
            'password_confirmation' => 'required|same:password',
        ];
    }


    public function attributes()
    {
        return [
            'old_password' => '原密码',
            'password' => '密码',
            'password_confirmation' => '确认密码',
        ];
    }

    public function messages()
    {
        return [

        ];
    }
}
