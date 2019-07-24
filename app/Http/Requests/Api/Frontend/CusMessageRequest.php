<?php

namespace App\Http\Requests\Api\Frontend;

use App\Http\Requests\Api\FormRequest;
class CusMessageRequest extends FormRequest
{


    public function rules()
    {
        return [
            'message' => 'required',
        ];
    }


    public function attributes()
    {
        return [
            'message' => '常用语',
        ];
    }

    public function messages()
    {
        return [
            'message.required' => '常用语 不能为空',
        ];
    }
}
