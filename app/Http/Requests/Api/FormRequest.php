<?php
namespace App\Http\Requests\Api;
use Dingo\Api\Exception\ValidationHttpException;
use Dingo\Api\Http\FormRequest as BaseFormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class FormRequest extends BaseFormRequest
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



//    public function validate()
//    {
//        if ($this->authorize() === false) {
//            throw new AccessDeniedHttpException();
//        }
//
//        $validator = app('validator')->make($this->all(), $this->rules(), $this->messages());
//        if ($validator->fails()) {
//            //return $this->failed($validator->errors()->first(), 422);
//            throw new ValidationHttpException($validator->errors());
//        }
//    }

}