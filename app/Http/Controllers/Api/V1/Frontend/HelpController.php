<?php
/**
 * Created by PhpStorm.
 * User: Watanabe Dai
 * Date: 2019/5/29
 * Time: 16:14
 */
namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use App\Models\HelpDoc;
class HelpController extends Controller{

    //帮助文档列表
    public function index(){
        $data = HelpDoc::paginate(15);
        return $this->success($data);
    }

    //下载文件
    public function download(Request $request) {
        $id = $request->input('id');
        if (empty($id)) {
            return $this->message('id不能为空','error');
        }

        $record = HelpDoc::find($id);
        if (collect($record)->isEmpty()) {
            return $this->message('操作异常','error');
        }

        $data = ['file_path'=>$record->file_path];
        return $this->success($data);
    }
}