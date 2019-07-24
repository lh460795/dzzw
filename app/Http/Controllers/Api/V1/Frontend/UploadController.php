<?php
/**
 * Notes: 附件管理
 * User: lh
 * Date: 2019-05-06
 */
namespace App\Http\Controllers\Api\V1\Frontend;

use App\Models\Upload;
use Illuminate\Http\Request;
use App\Traits\Api\ApiResponse;
use App\Service\UploadFile;
use App\Http\Controllers\Api\Controller;

class UploadController extends Controller
{

    public function upload(Request $request)
    {
        $config = config('Upload.upload');

        $fileField = "file";        //附件提交的name值
        $district =  'xiaogan';   //附件的文件夹名
        $uppath=$request->get('uploadpath');  //上传保存文件地址目录
        if(empty($uppath))
        {
            return $this->failed('操作失败');
        }
        $uppath=Upload::formatBypath($uppath);
        $pathFormat="/uploads/".$uppath."/{yyyy}{mm}{dd}/{time}{rand:6}";
//        dd($pathFormat);
        $upConfig = array(
            "pathFormat" => $pathFormat,
            "maxSize" => $config['fileMaxSize'],
            "allowFiles" => $config['fileAllowFiles'],
            "fieldName" => $fileField,
            "district" => $district,
        );
//        dd($request->file($fileField));
        if($request->hasFile($fileField))
        {
            $result = with(new UploadFile($upConfig, $request))->upload();
            return $this->success($result);
        }else{
//            return response()->json(['code'=>1,'msg'=>'上传失败']);
            return $this->failed('上传失败');
        }

    }
    public function create()
    {
        $file_type = Upload::file_type();
        return view('upload.create',compact('file_type'));
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $ids = $request->get('ids');
        if (empty($ids)){
            return $this->failed('请选择删除项');
        }
        foreach (Upload::whereIn('id',$ids)->get() as $model){
            //清除中间表数据
            //$model->tags()->sync([]);
            //删除文章
            $model->delete();
        }
        return $this->success('删除成功');
    }

}
