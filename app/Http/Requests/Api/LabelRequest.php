<?php

namespace App\Http\Requests\Api;

class LabelRequest extends FormRequest {

	/**
	 * 判断请求用户是否经过授权
	 *
	 * @return bool
	 */
	public function authorize(){
	    return true;
	}

	//验证
    public function rules(){
        // dd($this->name);
        if($this->method() == 'POST' || $this->method() == 'PUT'){
            return [
            	'name'      => 'required|unique:label,name,' . $this->id,  // 强制一个唯一规则来忽略给定ID
                'sort'      => 'numeric|min:0'
            ];
        }
        return [];
    }

    //消息提示
    public function messages(){
    	return [
    		'name.required'      => '标签名称必须填写',
    		'name.unique'        => '标签名称已经存在',
            'sort.numeric'       => '排序值必须为数字',
            'sort.min'           => '排序值必须大于等于0'
    	];
    }

}