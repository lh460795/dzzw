<?php

namespace App\Http\Requests\Api\Frontend;

use App\Http\Requests\Api\FormRequest;
class NoticationRequest extends FormRequest
{


    public function rules()
    {
        return [
            'id' => 'required|array',
        ];
    }


    public function attributes()
    {
        return [
            'id' => '消息id',
        ];
    }

    public function messages()
    {
        return [
            'ids.required' => '消息id 不能为空',
            'ids.array' => '消息id 必须是一个数组',
        ];
    }
}
