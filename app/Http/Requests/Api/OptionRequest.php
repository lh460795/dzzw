<?php

namespace App\Http\Requests\Api;

use App\Http\Requests\Api\FormRequest;
class OptionRequest extends FormRequest
{


    public function rules()
    {
        return [
            'key' => 'required|unique:options',
            'value' => 'required',
        ];
    }


    public function attributes()
    {
        return [
            'key' => '配置项',
            'value' => '配置值'
        ];
    }

    public function messages()
    {
        return [

        ];
    }
}
