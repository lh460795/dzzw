<?php
/**
 * Notes: 附件管理
 * User: lh
 * Date: 2019-05-06
 */
namespace App\Http\Controllers\Api\V1\Backend;

use App\Models\Upload;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;

class UploadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {

        $model = Upload::query();
        if ($request->get('file_type')){
            $model = $model->where('file_type',$request->get('file_type'));
        }
        if ($request->get('filename')){
            $model = $model->where('filename','like','%'.$request->get('filename').'%');
        }
        //\DB::connection()->enableQueryLog();  // 开启QueryLog
        $res = $model->orderBy('created_at','desc')->paginate($request->get('limit',5))->toArray();
        //dump(\DB::getQueryLog());
        if(!empty($res['data'])){
            foreach ($res['data'] as $k=>$val){
                $res['data'][$k]['file_type']= Upload::formatByfile_type($val['file_type']);
            }
        }
        $data = [
            'code' => 0,
            'msg'   => '正在请求中...',
            'count' => $res['total'],
            'data'  => $res['data']
        ];
        return response()->json($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $file_type = Upload::file_type();
        return view('upload.create',compact('file_type'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
            $upolad=new Upload();
        if($request->hasFile('files')){
            $res = $request->file('files');
            $list=array();
            foreach ($res as $key=>$val)
            {
               $list[] = $upolad->upload($val);
            }
            return $this->success($list);
        }else{
            return $this->failed('上传失败');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $upload = Upload::findOrFail($id);
        if (!$upload){
            return $this->failed('记录不存在');
        }
        $file_type = Upload::file_type();
        $upload->file_type=$file_type;
        //分类
//        $categorys = Category::with('allChilds')->where('parent_id',0)->orderBy('sort','desc')->get();
//        //标签
//        $tags = Tag::get();
//        foreach ($tags as $tag){
//            $tag->checked = $article->tags->contains($tag) ? 'checked' : '';
//        }

        return $this->success($upload);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $upload = Upload::findOrFail($id);
        $res = $request->all();
        $file = $res['file'];
        $ress = Upload::Upload($file);
        if ($upload->update($ress)){
            return $this->success('修改附件成功');
        }else{
            return $this->failed('修改失败');
    }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $id = $request->get('id');
        if (empty($id)){
            return $this->failed('请选择删除项');
        }
        foreach (Upload::whereIn('id',$id)->get() as $model){
            //清除中间表数据
            //$model->tags()->sync([]);
            //删除文章
            $model->delete();
        }
        return $this->success('删除成功');
    }

}
