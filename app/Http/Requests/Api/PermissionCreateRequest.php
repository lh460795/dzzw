<?php

namespace App\Http\Requests\Api;



class PermissionCreateRequest extends FormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'  => 'required|unique:permissions|max:200',
            'display_name'  => 'required',
            'parent_id' => 'required'
        ];
    }

    public function attributes()
    {
        return [
            'name' => '权限名称',
            'display_name' => '显示名称',
        ];
    }

    public function messages()
    {
        return [

        ];
    }
}
