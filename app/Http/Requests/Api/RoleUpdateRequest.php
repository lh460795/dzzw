<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\FormRequest;

class RoleUpdateRequest extends FormRequest
{

    public function rules()
    {
        return [
            'id' => 'required|integer',
            'name'=>'required|unique:roles,name,'.$this->input('id').',id|max:200',
            'display_name'  => 'required'
        ];
    }

    public function attributes()
    {
        return [
            'name' => '角色名称',
            'display_name' => '显示名称',
        ];
    }

    public function messages()
    {
        return [

        ];
    }
}
