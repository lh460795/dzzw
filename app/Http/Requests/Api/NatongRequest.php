<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class NatongRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $res = request()->all();

        $path = request()->path();
        $result = explode('/', $path);
        $actions = end($result);

        if ($actions == 'update') {
            $return = [];
            if (empty($res['pid'])) {
                $return['pid'] = 'required';
            }
            if (empty($res['uid'])) {
                $return['uid'] = 'required';
            }
            if (empty($res['is_incor'])) {
                $return['is_incor'] = 'required';
            }
            if (empty($res['now_incor'])) {
                $return['now_incor'] = 'required';
            }
        } else {
            $data = json_decode($res['data'], true);

            $units_id = $data['units_id'] ?? '0';

            if (empty($units_id)) {
                $return['units_id'] = 'required';
            } else {
                $return = [];
            }

        }


        return $return;

//        return [
//            'type' => 'array',
//            'is_incor' => 'array',
//            'units_id' => 'array',
//            'pid' => 'numeric',
//            'user_units' => 'required|numeric',
//        ];

    }


    public function messages()
    {
        parent::messages();
        return [
            'required' => ':attribute不能为空',
            'numeric' => ':attribute必须是数字',
            'max' => ':attribute长度（数值）不应该大于 :max',
        ];
    }

    public function attributes()
    {
        parent::attributes();
        return [
            'type' => '类型',
            'is_incor' => '纳统状态',
            'units_id' => '单位id',
            'pid' => '项目id',
            'is_incor' => '提交纳统状态',
            'now_incor' => '当前纳统状态',
            'uid' => '用户id',
            'natong'=>'输入相关信息'
//            'content' => '内容'
        ];
    }
}