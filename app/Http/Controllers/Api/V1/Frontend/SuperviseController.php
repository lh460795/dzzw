<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Events\PublishEvent;
use App\Models\Project;
use App\Models\Supervise;
use App\Models\SuperviseReply;
use App\Models\SuperviseTemplate;
use App\Models\Upload;
use App\Service\PendingService;
use App\Work\Model\FlowProcess;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Api\SuperviseRequest;

class SuperviseController extends Controller
{
    protected $request;
    protected $service;


    public function __construct(Request $request,PendingService $service)
    {
        $this->request = $request;
        $this->service = $service;
    }

    /*
     * 督办项目列表/被督办列表
     */
    public function index()
    {
        $perpage = $this->request->per_page ?? 5;

        $orderBy = $this->request->order_by ?? 'created_at';

        $sort = $this->request->sort ?? 'desc';

        $query = Project::has('supervise');

        //判断当前用户权限
        $user = Auth::user();

        $hasPermission = $this->service->hasPermission();

        if(!$hasPermission) {
            //五化办及以上用户返回所有数据
            //判断是否为分管领导
            $is_fenguan =  $this->service->hasPermission('分管副市长');

            if($is_fenguan) {

                $query->where(['fen_uid' => $user->id]);

            }else{

                //判断是否为立项单位操作员
                $is_reporter = $this->isReportingOfficer();

                if($is_reporter) {

                    $query->where(['uid' => $user->id]);

                }else{

                    //查询当前用户所属流程线
                    $process = FlowProcess::where(['auto_sponsor_ids' => $user->id])->get(['flow_id']);

                    $process_ids = array_column($process,'flow_id');

                    $fenguans = FlowProcess::whereIn('flow_id',$process_ids)
                        ->where(['process_name' => '分管副市长'])
                        ->get(['auto_sponsor_ids']);

                    $fen_uids = array_column($fenguans,'auto_sponsor_ids');

                    $query->whereIn('fen_uid',$fen_uids);

                }
            }
        }

        $res = $query->withCount('supervise')
            ->orderBy($orderBy,$sort)
            ->paginate($perpage);

        return $this->respond($res);
    }

    /**
     * 判断是否为立项单位操作员
     * @return bool
     */
    public function isReportingOfficer()
    {
        $user = Auth::user();

        $roles = $user->role->toArray();

        $role_names = array_column($roles,'name');

        if(in_array('立项单位操作员',$role_names)) {
            return true;
        }else{
            return false;
        }
    }

    /*
     * 督办函列表/被督办
     */
    public function superviseList()
    {

        $perpage = $this->request->per_page ?? 5;

        $hasPermission = $this->service->hasPermission();

        $query = Supervise::query();

        if(isset($this->request->status)) {
            $query->where(['status' => $this->request->status]);

        }

        if($hasPermission) {
            $query->where(['uid' => Auth::id()]);
        }else{
            $query->where(['touid' => Auth::id()]);
        }

        $data = $query->with([
                    'upload',
                    'reply' => function($query) {
                        $query->with('upload');
                    }
                ])
                ->orderBy('addtime','desc')
                ->paginate($perpage);

        return $this->respond($data);
    }

    /*
     * 发督办函
     */
    public function store(SuperviseRequest $request)
    {
        $data = $request->all();

        $model = Supervise::create($data);

        if($model) {

            //附件路径入库
            if(isset($data['files']) && !empty($data['files'])) {

                Upload::upload($data['files']);

            }

            event(new PublishEvent($model));

            return $this->success($model);

        }else{

            return $this->failed('操作失败');

        }
    }

    /*
     * 反馈督办
     */
    public function reply(Request $request)
    {
        $this->validate($request, [
            'content' => 'required',
            'duban_id' => 'required',
            'touid' => 'required',
        ]);

        $data = $request->all();

        $model = SuperviseReply::create($data);

        if($model) {

            //附件路径入库
            if(isset($data['files']) && !empty($data['files'])) {

                Upload::upload($data['files']);

            }

            return $this->success($model);

        }else{

            return $this->failed('操作失败');

        }
    }

    /*
     * 确认整改
     */
    public function confirm($id)
    {
        $res = Supervise::where(['id' => $id])->update(['status' => Supervise::STATUS_CONFIRMED]);

        if($res) {

            return $this->success([]);

        }else{

            return $this->failed('操作失败');

        }
    }

    /**
     * 获取模板
     * @return mixed
     */
    public function getTemplate()
    {
        $res = SuperviseTemplate::all();
        return $this->respond($res);
    }

    /**
     * 添加模板
     * @param Request $request
     */
    public function addTemplate(Request $request)
    {
        $this->validate($request, [
            'content' => 'required',
        ]);

        $res = SuperviseTemplate::create($request->all());

        if($res){

            return $this->success($res);

        }else{

            return $this->failed('操作失败');

        }
    }

    /**
     * 修改模板
     */
    public function updateTemplate(Request $request)
    {
        $this->validate($request, [
            'id' => 'required',
            'content' => 'required',
        ]);

        $data = $request->all();

        $res = SuperviseTemplate::where(['id' => $data['id']])->update(['content' => $data['content']]);

        if($res){

            return $this->success([]);

        }else{

            return $this->failed('操作失败');

        }
    }

    /**
     * 删除模板
     * @param $id
     * @return mixed
     */
    public function deleteTemplate($id)
    {
        $res = SuperviseTemplate::destroy($id);

        if($res){

            return $this->success([]);

        }else{

            return $this->failed('操作失败');

        }
    }


    //数据迁移方法
    public function shuju()
    {
        $list= Supervise::select('id','pid')->get()->toArray();
        dd($list);
        foreach ($list as $key=>$value)
        {
            //附件
            $res=Db::table('wh_upload_duban')->where(['duban_id'=>$value['id'],'pid'=>$value['pid']])->get()->toArray();
            if(!empty($res))
            {
                //附件转移
                foreach ($res as $k=>$v)
                {
                    $upload['pid']=$v->pid; //项目主表id
                    $upload['relation_id']=$v->duban_id;//关联表id
                    $upload['uid']=$v->uid;//关联表id
                    $upload['url']=$v->url;//文件路径
                    $upload['filename']=$v->filename;//文件名(原名)
                    $upload['file_new_name']=$v->file_new_name;//文件名(重命名)
                    $upload['ext']=$v->ext;//文件后缀
                    $upload['file_type']=7;//附件类型 督办附件
                    $upload['add_time']=$v->add_time;//添加日期
                    $upload['created_at']=date('Y-m-d h:i:s',$v->add_time);//添加日期
                    Upload::insert($upload);
                }
            }
        }
    }
}
