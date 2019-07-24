<?php

namespace App\Http\Requests\Api\Frontend;

use App\Http\Requests\Api\FormRequest;
class AttentionRequest extends FormRequest
{


    public function rules()
    {
        return [
            'ids' => 'required|array',
        ];
    }


    public function attributes()
    {
        return [
            'ids' => '项目id',
        ];
    }

    public function messages()
    {
        return [
            'ids.required' => '项目id 不能为空',
            'ids.array' => '项目id 必须是一个数组',
        ];
    }
}
