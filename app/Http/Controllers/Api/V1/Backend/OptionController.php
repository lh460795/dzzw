<?php

namespace App\Http\Controllers\Api\V1\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use App\Models\Option;
use Option as Opt;
use App\Http\Requests\Api\OptionRequest;

class OptionController extends Controller
{

    //配置列表
    public function index(Request $request)
    {
        $data = Option::get();
        return $this->success($data);
    }

    public function create(OptionRequest $request) {
        $data = [
            'key' => $request->input('key'),
            'value' => $request->input('value'),
        ];

        try {
            Option::create($data);
            return $this->message('新增成功');
        } catch (\Exception $e) {
            return $this->failed('新增失败',400);
        }
    }

    public function update(Request $request) {
        $id = $request->input('id');
        $value = $request->input('value');
        if (empty($id) || empty($value)) {
            $this->failed('参数错误');
        }

        try {
            Option::where('id', $id)->upate(['value'=>$value]);
            return $this->message('修改成功');
        }catch (\Exception $e) {
            return $this->failed('修改失败', 400);
        }
    }
}
