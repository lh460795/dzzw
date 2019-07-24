<?php

namespace App\Http\Requests\Api;

class ArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize () {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules () {
        if($this->method() == 'POST'){
            return [
                'uid' => 'required'
            ];
        }
        return [];
    }

    public function messages(){
        return [
            'uid.required' => '用户必须存在'
        ];
    }
}
