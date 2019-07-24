<?php

namespace App\Http\Requests\Api;



class PermissionUpdateRequest extends FormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required|integer',
            'name'=>'unique:permissions,name,'.$this->input('id').',id|max:200',
            'display_name'  => ''
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
