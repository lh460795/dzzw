<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SuperviseRequest extends FormRequest
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
            'pid'   => 'required',
            'touid'   => 'required',
            'content' => 'required',
            'limi_time' => 'sometimes',
        ];
    }

    public function messages()
    {
        parent::messages();
        return [
            'required'  => ':attribute不能为空',
            'numeric'   => ':attribute必须是数字',
            'max'       => ':attribute长度（数值）不应该大于 :max',
        ];
    }

    public function attributes()
    {
        parent::attributes();
        return [
            'pid'   => '项目ID',
            'touid'   => '发送对象ID',
            'content' => '内容',
            'limi_time' => '限制时间',
        ];
    }
}
