<?php
namespace App\Http\Controllers\Api\V1\Frontend;

use App\Models\Upload;
use App\Work\Model\RunLog;
use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Complete;
use App\Models\Unit;
use App\Work\Model\FlowProcess;
use App\Work\Model\Run;
use App\Work\Model\Flow;
use App\Work\Model\RunProcess;
use App\Work\Repositories\ProcessRepo;
use App\Work\Workflow;
use Auth;
use Illuminate\Support\Facades\DB;

class CompleteController extends Controller {


    //完结项目申请列表
    public function index(Request $request)
    {
        $paginate = $request->get('per_page') ?? 15;
        $res = Project::where([
            'status_flow' => 2,
            'is_adjust'=>0,
            'is_complete'=>0
        ])->filter($request->all())
            ->select('id', 'units_id','year','is_year', 'pname', 'type', 'wf_id', 'tianbiao_date','m_score', 'pro_status','progress')
            ->orderBy('progress','desc')
            ->paginate($paginate);

        foreach ($res as $key => $value) {
            $value->typename = get_type_name($value->type);
            $value->corpname = Unit::getName($value->units_id);  //简称
            $value->tianbiao_date = date('Y-m-d',$value->tianbiao_date);
            $value->wf_id = Flow::find($value->wf_id)->flow_name;
        }
        return $this->success($res);
    }

    //出库申请页面
    public function apply(Request $request)
    {
         //1表示完结出库申请  2表示未完结
        $id=$request->get('id');
        $status=$request->get('status');
        $res = Project::where(['id'=>$id])->first(['id','uid','year','is_year','units_id','pname','zhuban','zhu_fuze','xieban','xie_fuze','lianxiren','tianbiaoren','tianbiao_date','is_complete','is_adjust']);
        if($res){
            if($res->is_complete != '0')
            {
                return $this->failed('该项目已申请出库');
            }
            if($res->is_adjust != '0')
            {
                return $this->failed('该项目已申请调整');
            }
            $res->zhuhan = Unit::getName($res->units_id);   //项目申报人所在单位名简称
            $res->alias_name = Unit::getAliasName($res->units_id); //项目申报人所在单位全称
            $res->xieban=get_xieban_list($res->xieban,$res->xie_fuze);
            if($res->tianbiao_date)
            {
                $res->tianbiao_date=date('Y-m-d',$res->tianbiao_date);
            }
            $res->statuswj=$status;
            return $this->success($res);
        }else{
            return $this->failed('操作失败');
        }
    }


    //出库申请提交页面
    public function store(Request $request)
    {
        $pid=$request->id;
        $status=$request->status;
        $fileList=$request->fileList;
        $fileList=json_decode($fileList);  //附件
        $project = Project::with(['complete'=>function($query)use($pid){
            $query->where('pid',$pid);
        }])->findOrFail($pid);
        if($project->is_complete != '0')
        {
            return $this->failed('该项目已申请出库');
        }
        if($project->is_adjust != '0')
        {
            return $this->failed('该项目已申请调整');
        }
        $rules = [
            'id'=>'required',
            'target' => 'required',
            'situation' => ['required', 'min:500'],
            'status' => 'required'
        ];
        $messages = [
            'target.required' => '工作目标及任务概述不能为空',
            'situation.required' => '实际完成情况不能为空',
            'situation.min' => '实际完成情况不能少于500字',
        ];
        $validator = \Validator::make($request->all(), $rules, $messages)->validate();
        $uid = $project->uid;
        if($status == 1)
        {
            $file_type=4;
        }elseif($status == 2){
            $file_type=5;
        }
        \DB::beginTransaction();
        try {
            if($project->complete)
            {
                //驳回重新申报
                $complete = Complete::find($project->complete->id);
                $complete->pid = $pid;
                $complete->target = $request->target;
                $complete->status = $status;  // 完结状态  1完结  2未完结
                $complete->situation = $request->situation;
                $complete->save();
                Upload::where(['pid'=>$pid,'relation_id'=>$project->complete->id,'file_type'=>$file_type])->delete();
                $relation_id=$project->complete->id;
            }else{
                $complete = new Complete();
                $complete->pid = $pid;
                $complete->target = $request->target;
                $complete->status = $status;  // 完结状态  1完结  2未完结
                $complete->situation = $request->situation;  // 停建
                $complete->type = $project->type;
                $complete->save();
                $relation_id=$complete->id;
            }
            if(!empty($fileList))
            {
                Upload::upload($pid,$relation_id,$uid,$file_type,$fileList);
            }
            // 开启工作流
            $data_work = [
                'wf_type' => 'complete', //业务表
                'wf_fid' => $complete->id,//业务表主键ID
                'wf_id' => $project->wf_id,//流程表主键id
                'new_type' => '0',//紧急程度
                'check_con' => 'pass',//审核意见
            ];

            $flow = Workflow::startworkflow($data_work, $uid);

            if ($flow['code'] == 1) {
                $flow['complete_id'] = $complete->id;
                $project->is_complete = $status; //完结未完结
                $project->save();

                // 系统自动审核
                Complete::autoCheck($complete->id, $flow['run_id'], $project->wf_id);
                \DB::commit();
            } else {
                \Db::rollback();
                return $this->failed('提交失败！');
            }
            return $this->success($flow);

        } catch (\Exception $e) {
            \DB::rollback();
            return $this->failed('提交失败！');
        }

    }


    //出库项目待审核、审核中、已出库项目列表
    public function lists(Request $request)
    {
        $user = Auth::guard('api')->user(); //登录人uid
        //$role_id = Auth::guard('api')->user()->role_id ??1;
        $uid = $request->get('uid') ??1;//测试接口用
        $role_id =$request->get('role_id');  //登录人权限
        $menu = $request->get('menu') ?? 'yck';//菜单url
        $paginate = $request->get('per_page') ?? 15;

        //主表条件查询

        $map = [];//条件查询
        if ($role_id =='1') { //立项单位
            if ($menu == 'shz') { //审核中
                $map = [
                    ['wf_run.uid', '=', $uid],
                    ['wf_run.status', '=', 0]
                ];
            } elseif ($menu == 'bh') { //驳回列表
                $map = [
                    ['wf_run_log.btn', '=', 'Back']
                ];
            } elseif ($menu == 'yck') { //建设中
                $map = [
                    ['wf_run.uid', '=', $uid],
                    ['wf_run.status', '=', 1],
                    ['complete.status_flow', '=', 2]
                ];
            }
        }elseif($role_id =='2'){ //业务科室
            $process_name='科室';
        }elseif($role_id =='3'){//副秘书长
            $process_name='副秘书长';
        } elseif($role_id =='4'){//分管副市长
            $process_name='分管副市长';
        } elseif($role_id =='5'){ //五化办
            $process_name='五化办';
        } elseif($role_id =='6'){ //常务副市长
            $process_name='常务副市长';
        } elseif($role_id =='7'){ //市长
            $process_name='市长';
        }

        if($role_id !='1'){ //除开立项单位 因为立项单位不在步骤中
            if($menu =='dsh'){//当前角色自己审核
                $flow_keshi_id = FlowProcess::getprocessIdByuid($uid,$process_name)->id;
                $map = [
                    ['wf_run.run_flow_process', '=', $flow_keshi_id],
                    ['wf_run.status', '=', 0]
                ];
            }elseif($menu =='shz'){//审核中
                $flow_process_ids =  FlowProcess::getprocessIdsByflow_id($uid,$process_name);
                $map = [
                    ['wf_run.status', '=', 0]
                ];
            }elseif($menu =='bh'){ //我驳回项目
                $map = [
                    ['wf_run_log.btn', '=', 'Back'],
                    ['wf_run_log.uid', '=', $uid]
                ];
            }elseif($menu =='yck'){ //建设中
                $flow_process_ids =  FlowProcess::getprocessIdsByflow_id($uid,$process_name);
                $map = [
                    ['wf_run.status', '=', 1],
                    ['complete.status_flow', '=', 2]
                ];
            }
        }
        // 构建子查询
        if(!empty($flow_process_ids)){
            $query_build = RunProcess::leftJoin('wf_run','wf_run.id','=','wf_run_process.run_id')
                ->leftjoin('wf_run_log','wf_run_log.from_id','=','wf_run.from_id')
                ->leftjoin('complete','complete.id','=','wf_run.from_id')
                ->groupBy('wf_run_process.run_id')
                ->where($map)
                ->where('wf_run.from_table','=','complete') //关联业务表
                ->whereIn('wf_run_process.run_flow_process',$flow_process_ids)// 查询条件有区别
                ->select('complete.pid');
        }else{
            $query_build = RunProcess::leftJoin('wf_run','wf_run.id','=','wf_run_process.run_id')
                ->leftjoin('wf_run_log','wf_run_log.from_id','=','wf_run.from_id')
                ->leftjoin('complete','complete.id','=','wf_run.from_id')
                ->groupBy('wf_run_process.run_id')
                ->where($map)
                ->where('wf_run.from_table','=','complete') //关联业务表
                ->select('complete.pid');
        }
        //子查询调用方法
        $result = Project::whereIn('id', function($query) use ($query_build){
            $query->from(DB::raw("({$query_build->toSql()}) as pid"));
            $query->mergeBindings($query_build->getQuery());
        })
            ->filter($request->all()) //查询过滤
            ->paginate($paginate);
        foreach ($result as $key => $value) {
            $value->typename = get_type_name($value->type);
            $value->corpname = Unit::getName($value->units_id);  //简称
            $value->tianbiao_date = date('Y-m-d',$value->tianbiao_date);
            $value->wf_id = Flow::find($value->wf_id)->flow_name;
        }
        return $this->success($result);

    }


    //项目审核详情页
    public function audit(Request $request)
    {
        $id=$request->get('id');
        $uid=$request->get('uid');
        $project=Project::where(['id'=>$id])->first(['id','units_id','pname','zhuban','zhu_fuze','xieban','xie_fuze','lianxiren','tianbiaoren','tianbiao_date']);
        if($project)
        {
            if($project->tianbiao_date)
            {
                $project->tianbiao_date=date('Y-m-d',$project->tianbiao_date);
            }
            $project->zhuhan = Unit::getName($project->units_id);   //项目申报人所在单位名简称
            $project->alias_name = Unit::getAliasName($project->units_id); //项目申报人所在单位全称
            $project->xieban = get_xieban_list($project->xieban,$project->xie_fuze);
            $complete=Complete::where(['pid'=>$project->id])->first();
            $sponsor_ids=FlowProcess::getRunFlowProcess('complete',$complete->id); //项目当前审核人
            if($uid == $sponsor_ids)
            {
                //表示当前登录人为项目审核人
                $project->is_sh=1;
            }else{
                $project->is_sh=0;
            }
            $project->target=$complete->target;
            $project->situation=$complete->situation;
            $project->complete_id=$complete->id;
            $project->statuswj=$complete->status;
            $log = RunLog::log($complete->id, 'complete');
            $fenguan = [
                'username'=>'',
                'rolename'=>'',
                'content'=>'',
                'created_at'=>''
            ];
            $leader = [
                0=>[
                    'username'=>'',
                    'rolename'=>'',
                    'content'=>'',
                    'created_at'=>''
                ],
                1=>[
                    'username'=>'',
                    'rolename'=>'',
                    'content'=>'',
                    'created_at'=>''
                ]
            ];
            foreach ($log as $k=>$v)
            {
                if($v['btn']== '通过')
                {
                    if($v['rolename'] == '分管副市长')
                    {
                        $fenguan['rolename']=$v['rolename'];
                        $fenguan['username']=$v['username'];
                        $fenguan['content']=$v['content'];
                        $fenguan['created_at']=date('Y年m月d日',strtotime($v['created_at']));

                    }
                    if($v['rolename'] == '市长')
                    {
                        $leader[0]['username'] = $v['username'];
                        $leader[0]['rolename'] = $v['rolename'];
                        $leader[0]['content'] = $v['content'];
                        $leader[0]['created_at'] = date('Y年m月d日',strtotime($v['created_at']));
                    }
                    if($v['rolename'] == '常务副市长')
                    {
                        $leader[1]['username'] = $v['username'];
                        $leader[1]['rolename'] = $v['rolename'];
                        $leader[1]['content'] = $v['content'];
                        $leader[1]['created_at'] = date('Y年m月d日',strtotime($v['created_at']));
                    }
                }
            }
            $log=array_reverse($log);
            $project->fenguan=$fenguan;
            $project->leader=$leader;
            $project->log=$log;
            //附件
            if($complete->status == 1)
            {
                $file_type=4;
            }elseif ($complete->status == 2){
                $file_type=5;
            }
            $fileList=Upload::where(['pid'=>$project->id,'relation_id'=>$complete->id,'file_type'=>$file_type])->get();
//            $filelist= fileurl_replace($filelist);
            $project->fileList=$fileList;
            return $this->success($project);
        }else{
            return $this->failed('操作失败');
        }
    }


    //项目审核操作
    public function check(Request $request)
    {
        $id=$request->get('id');  //完结表id
        $uid = $request->get('uid');
        $role_id = $request->get('role_id');
        $check_con = $request->get('check_con') ?? '通过'; //审核/驳回意见
        $wf_backflow = $request->get('wf_backflow')??'';//模拟退回到科室 2 flow_process ID

        try{
            $complete = Complete::with('project:id,wf_id')->find($id);

            $workflow = Workflow::workflowInfo($id,'complete',['uid'=>$uid,'role'=>$role_id]);

            // 判断重复审核提交
            if($workflow['run_id']){
                $message = Workflow::workflowCheck($workflow,$uid,$role_id);//后台验证
                if($message['code'] =='-1'){
                    return $this->failed($message['msg']);
                }

                if ($workflow['wf_mode'] !='2'){
                    //dd($data['flowinfo']);
                    $npid = $workflow['nexprocess']->id ?? $workflow['nexprocess']['id']; //下一步骤id
                }else{
                    $npid = $workflow['process']->process_to;
                }
                // dd($npid);
                if($wf_backflow !=''){
                    $submit_to_save='back';
                    $backflow_id =0; //驳回步骤id：$wf_process->id 退回立项单位 0
                    $project=Project::find($complete->pid);
                    $project->is_complete=0;
                    $project->save();
                }else{
                    $submit_to_save='ok';
                    $backflow_id='';
                }
                $save =[
                    "wf_title" =>  "",
                    "wf_fid" =>  $id,  // 调整表主键id
                    "wf_type" =>  'complete',
                    "flow_id" =>  $complete->project->wf_id,
                    "flow_process" =>  $workflow['flow_process'],
                    "run_id" =>  $workflow['run_id'],
                    "run_process" =>  $workflow['run_process'],
                    "npid" =>  $npid??"",
                    "wf_mode" =>  $workflow['wf_mode'],
                    "sup" => "",
                    "check_con" =>  $check_con,
                    "wf_backflow" =>$backflow_id,
                    "btodo" => "",
                    "wf_singflow" =>  "",
                    "submit_to_save" =>  $submit_to_save, //
                    "art" =>  "",
                    "sing_st"=>  $workflow['sing_st']
                ];

                $result = Workflow::workdoaction($save, $uid);//获取下一步骤信息 根据按钮值 做提交 或者回退处理
                $compl=Complete::find($id);
                if($compl->status_flow == 2)
                {
                    if($compl->status == 1)
                    {
                        //完结
                        $com=['pro_status','=',4];
                    }elseif ($compl->status == 2)
                    {
                        //未完结
                        $com=['pro_status','=',6];
                    }
                   Project::where(['id'=>$compl->pid])->update($com);
                }
                return $this->success('操作成功');
            }
        }catch (\Exception $e) {
            return $this->failed('操作失败');
        }
    }


    //数据迁移方法
    public function shuju()
    {
       $list= Db::table('wh_un_complete')->select('id','ago_id','targets','situation','status','adjus_time')->get();
        dd($list);
       foreach ($list as $key=>$value)
       {
           $data['pid']=$value->ago_id;
           $data['type'] = $value->type;
           $data['target']=$value->targets;
           $data['situation']=$value->situation;
           $data['status']=2;  //完结
           if($value->status == 11){
               $data['status_flow'] = -1;  //驳回
           }elseif ($value->status == 6)
           {
               $data['status_flow'] = 2;  //通过
           }else{
               $data['status_flow'] =1;  //流程中
           }
           $data['uptime']=$value->adjus_time;
           $data['created_at']=date('Y-m-d h:i:s',$value->adjus_time);
           $complete=Complete::create($data);
           //附件
           $res=Db::table('wh_upload_un_complete')->where(['pid'=>$value->id])->get()->toArray();
           if(!empty($res))
           {
               //附件转移
               foreach ($res as $k=>$v)
               {
                   $upload['pid']=$value->ago_id; //项目主表id
                   $upload['relation_id']=$complete->id;//关联表id
                   $upload['uid']=$v->uid;//关联表id
                   $upload['url']=$v->url;//文件路径
                   $upload['filename']=$v->filename;//文件名(原名)
                   $upload['file_new_name']=$v->file_new_name;//文件名(重命名)
                   $upload['ext']=$v->ext;//文件后缀
                   $upload['file_type']=5;//附件类型 完结附件
                   $upload['add_time']=$v->add_time;//添加日期
                   $upload['created_at']=date('Y-m-d h:i:s',$v->add_time);//添加日期
                   Upload::insert($upload);
               }
           }
       }
    }

}

