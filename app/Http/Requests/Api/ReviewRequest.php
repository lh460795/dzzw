<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //            'tel' => 'required_without_all:mobile,email,address',
            //            'name'  => 'sometimes|max:20',  //sometimes的用意（不传则已，传则必须遵守规则）
            'content' => 'required|string',
        ];
    }

    public function messages()
    {
        parent::messages();
        return [
            //            'tel.required_without_all' => '当手机号码、Email、地址都为空时,电话号码不能为空',
            'required'  => ':attribute不能为空',
            'numeric'   => ':attribute必须是数字',
            'max'       => ':attribute长度（数值）不应该大于 :max',
        ];
    }

    public function attributes()
    {
        parent::attributes();
        return [
            'content' => '内容'
        ];
    }
}
