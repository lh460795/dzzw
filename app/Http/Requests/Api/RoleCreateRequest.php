<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\FormRequest;

class RoleCreateRequest extends FormRequest
{

    public function rules()
    {
        return [
            'name'  => 'required|unique:roles|max:200',
            'display_name'  => 'required'
        ];
    }

    public function attributes()
    {
        return [
            'name' => '角色名称',
            'display_name' => '描述',
        ];
    }

    public function messages()
    {
        return [

        ];
    }
}
