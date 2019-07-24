<?php
/**
 * 项目主表表单验证
 */
namespace App\Http\Requests\Api;

class ProjectRequest extends FormRequest
{

    //表单验证
    public function rules()
    {
        //dd($this->request);
        switch ($this->method()) {
            case 'GET':
                {
                    return [
                        'id' => ['required','int','exists:project,id']
                    ];
                }
            case 'POST':
                {
                    return [
                        'pname' => ['required', 'max:60', 'unique:project,pname'],
                        'uid' => ['required','int'],
                        'type' => ['required','int'],
                    ];
                }
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
            default:
                {
                    return [

                    ];
                }
        }

    }

    public function messages()
    {
        return [
            'id.required'=>'项目ID必须填写',
            'id.exists'=>'项目ID不存在',
            'pname.unique' => '项目已经存在',
            'pname.required' => '项目不能为空',
            'uid.required' => '用户不能为空',
            'type.required' => '项目类型不能为空',
        ];
    }

}