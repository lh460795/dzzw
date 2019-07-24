<?php

namespace App\Http\Requests\Api\Frontend;

use App\Http\Requests\Api\FormRequest;
class TagRequest extends FormRequest
{


    public function rules()
    {
        return [
            'tag' => 'required',
        ];
    }


    public function attributes()
    {
        return [
            'tag' => '项目标识',
        ];
    }

    public function messages()
    {
        return [
            'message.required' => '项目标识 不能为空',
        ];
    }
}
