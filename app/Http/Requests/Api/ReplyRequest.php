<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplyRequest extends FormRequest
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
            'pid' => 'required_if:type,1|int',
            'reply_id' => 'required',
            'parent_id' => 'required_if:reply_id,0',
            'content' => 'required|string',
            'type' => 'required',
            'to_id' => 'required_if:reply_id,0',
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
            'pid' => '项目ID',
            'parent_id' => '顶级回复ID',
            'reply_id' => '被回复ID',
            'type' => '回复类型',
            'content' => '内容',
            'to_id' => '父级ID'
        ];
    }
}
