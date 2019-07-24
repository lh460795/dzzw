<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\Unit;
use App\Models\Upload;
use Auth;
use App\Models\Adjust;
use App\Work\Workflow;
use App\Models\ProjectPlanCustom;
use App\Models\ProjectPlanCustomHistory;
use App\Http\Resources\Api\V1\Frontend\ProjectResource;
use App\Work\Model\Flow;
use App\Work\Model\RunProcess;
use App\Http\Requests\Api\ProjectRequest;
use App\Models\Type;
use App\Work\Model\RunLog;
use App\Work\Model\FlowProcess;
use App\Work\Model\Run;
use App\Http\Controllers\Api\V1\Frontend\ProjectController;
use App\Service\ProjectService;
use App\Models\Progress;
use App\Models\ProjectProgressHistory;

// 调整模块控制器
class AdjustController extends Controller {

	protected $projectservice;

    public function __construct(ProjectService $projectService)
    {
        $this->projectservice = $projectService;
    }

	// 发起调整列表
	public function lists(Request $request){
		// dd($request->all());  
		// 审核通过的项目才能发起调整
		$paginate = $request->get('paginate') ?? 8;
		$res = Project::where(['status_flow' => 2, 'is_adjust' => 0, 'is_complete' => 0])
			->filter($request->all())
		  	->select('id', 'units_id', 'year','is_year','pname', 'type', 'wf_id', 'tianbiao_date', 'pro_status','progress','fen_uid')
		  	->paginate($paginate);
		foreach ($res as $key => $value) {
			$value->unit_name = Unit::getName($value->units_id);
			$value->typename = get_type_name($value->type);
			$value->flow_name = User::getUserName($value->fen_uid);
			$value->time = date('Y-m-d', $value->tianbiao_date);
		}
		
		return $this->success($res); 
	}

	//申请停建页面
	public function stopIndex(Request $request, $pid){
		$res = Project::where(['id'=>$pid])->first(['id','pname','units_id','zhuban','zhu_fuze','xieban','xie_fuze','tianbiaoren','tianbiao_date']);
		if($res){
			$res->unitsname = Unit::getName($res->units_id);       
			$res->units_alias_name = Unit::getAliasName($res->units_id);
			$xieban = explode('|', $res->xieban);
			foreach ($xieban as $k => $v) {
				$xieban[$k] = Unit::getName($v);
			}
			// $res->xieban = $xieban;
			$xie_fuze = explode("|", $res->xie_fuze);
			$res->zhuban = Unit::getName($res->zhuban); 
			$res->tianbiao_date = date('Y-m-d', $res->tianbiao_date);
			$xiebanData = [];
			foreach ($xieban as $key => $value) {
				$xiebanData[$key]['xieban'] = $value;
				
			}
			foreach ($xie_fuze as $k => $v) {
				$xiebanData[$k]['fuze'] = $v;
			}
// dd($xiebanData);
			$res->xiebanData = $xiebanData;
			return $this->success($res);
		}else{
			return $this->failed('操作失败');
		}
	}

	// 申请停建提交操作
	public function stopActive(Request $request, $pid, $status=2){  // status 为2 停建
		$project = Project::with(['adjust'=>function($query)use($pid){
			$query->where('pid',$pid);
		}])->findOrFail($pid);
		// dd($project->toArray());
		if($project->is_adjust != 0){
			return $this->failed('已提交调整');
		}elseif ($project->is_complete != 0) {
			return $this->failed('已提交完结');
		}
        $fileList=$request->fileList;
        $fileList=json_decode($fileList);  //附件
		$uid = $project->uid;
		$rules = ['stop_reason' => 'required'];
		$messages = ['stop_reason.required' => '项目调整事由及内容必须填写'];
		$validator = \Validator::make($request->all(), $rules, $messages)->validate();

		\DB::beginTransaction();
		try{
			if($project->adjust){
				// 驳回重新申报
				$adjust = Adjust::find($project->adjust->id);
				$adjust->pid = $pid;
				$adjust->stop_reason = $request->stop_reason;
				$adjust->is_adjust = $status;  // 停建
				$adjust->save();
                Upload::where(['pid'=>$pid,'relation_id'=>$adjust->id,'file_type'=>3])->delete();
                $relation_id=$adjust->id;
			}else{
				// 第一次提交
				$adjust = new Adjust();
				$adjust->pid = $pid;
				$adjust->stop_reason = $request->stop_reason;
				$adjust->is_adjust = $status;  // 停建
				$adjust->type = $project->type;
				$adjust->save();
                $relation_id=$adjust->id;
			}
            if(!empty($fileList))
            {
                Upload::upload($pid,$relation_id,$uid,3,$fileList);
            }
			$project->is_adjust = $status; //调整停建
			$project->save();
				// 开启工作流
			$data_work = [
                'wf_type' => 'adjust', //业务表
                'wf_fid' => $adjust->id,//业务表主键ID
                'wf_id' => $project->wf_id,//流程表主键id
                'new_type' => '0',//紧急程度
                'check_con' => 'pass',//审核意见
            ];

			$flow = Workflow::startworkflow($data_work, $uid);
			if($flow['code'] == 1){
				$flow['adjust_id'] = $adjust->id;
				// 系统自动审核
				Adjust::autoCheck($adjust->id, $flow['run_id'], $project->wf_id);
				\DB::commit();
			}else{
				\Db::rollback();
				return $this->failed('提交失败！');
			}
			return $this->success($flow);
			
		} catch (\Exception $e) {
            \DB::rollback();
            return $this->failed('提交失败！');
        }
	}

	// 停建审核页面
	public function stopAudit(Request $request, $adjust_id){
		$adjust = Adjust::with('project:id,units_id,year,type,pname,zhuban,zhu_fuze,xieban,xie_fuze,lianxiren,tianbiaoren,tianbiao_date')->find($adjust_id);
		if($adjust){
			$adjust->project->unit_name = Unit::getAliasName($adjust->project->units_id);
			$adjust->project->zhuban = Unit::getName($adjust->project->zhuban);
			$xieban = explode('|', $adjust->project->xieban);
			$xie_fuze = explode("|", $adjust->project->xie_fuze);

			$xiebanData = [];
			foreach ($xieban as $key => $value) {
				$xiebanData[$key]['xieban'] = Unit::getName($value);
				
			}
			foreach ($xie_fuze as $k => $v) {
				$xiebanData[$k]['fuze'] = $v;
			}
			$adjust->project->xiebanData = $xiebanData;

			// 审核日志
			$adjust->log = RunLog::log($adjust_id, 'adjust');
			// dd($adjust->log);
			// 领导审核意见
			$fenguan = [];
			$leader = [];
			foreach($adjust->log as $k=>$v){
				if($v['btn'] == '通过'){
					if($v['rolename'] == '分管副市长'){
                        $fenguan['rolename']=$v['rolename'];
                        $fenguan['username']=$v['username'];
                        $fenguan['content']=$v['content'];
                        $fenguan['created_at']=date('Y年m月d日',strtotime($v['created_at']));

                    }elseif($v['rolename'] == '市长'){
                        $leader[0]['username'] = $v['username'];
                        $leader[0]['rolename'] = $v['rolename'];
                        $leader[0]['content'] = $v['content'];
                        $leader[0]['created_at'] = date('Y年m月d日',strtotime($v['created_at']));
                    }elseif($v['rolename'] == '常务副市长'){
                        $leader[1]['username'] = $v['username'];
                        $leader[1]['rolename'] = $v['rolename'];
                        $leader[1]['content'] = $v['content'];
                        $leader[1]['created_at'] = date('Y年m月d日',strtotime($v['created_at']));
                    }
				}
			}
            $fileList=Upload::where(['pid'=>$adjust->pid,'relation_id'=>$adjust_id,'file_type'=>3])->get();
//            $fileList= fileurl_replace($fileList);
            $adjust->fileList=$fileList;
			$adjust->fenguan = $fenguan;
			$adjust->leader = $leader;			
			$adjust->project->tianbiao_date = date('Y-m-d', $adjust->project->tianbiao_date);
			return $this->success($adjust);
		}else{
			return $this->failed('操作失败！');
		}
	}

	// 申请修改项目页面
	public function editIndex(Request $request, $pid){
		$role = $request->role;
		if($role == 1){
			// 申报单位操作人
			$project = Project::where(['id' => $pid])->first(['id','uid','pname','type','fen_uid']);
			if($project){
				$project->typename = Type::get_type_name($project->type);
				$project->flow_name= User::getUserName($project->fen_uid);
				$project->fileList = Upload::where(['pid'=>$pid,'relation_id'=>$pid,'file_type'=>1])->get();
				return $this->success($project);
			}else{
				return $this->failed('pid不存在！');
			}
		}elseif($role == 5){
			// 五化办
			$adjust_id = $request->adjust_id;
			if($adjust_id){
				$info = Adjust::with('project:id,uid,pname,type,fen_uid')->find($adjust_id);
				$info->project->typename = Type::get_type_name($info->project->type);
				$info->project->flow_name= User::getUserName($info->project->fen_uid);
				$info->project->fileList = Upload::where(['pid'=>$pid,'relation_id'=>$pid,'file_type'=>1])->get();
				return $this->success($info);
			}else{
				return $this->failed('pid不存在！');
			}			
		}else{
			$project = Project::where(['id' => $pid])->first(['id','uid','pname','type','fen_uid']);
			if($project){
				$project->typename = Type::get_type_name($project->type);
				$project->flow_name= User::getUserName($project->fen_uid);
				$project->fileList = Upload::where(['pid'=>$pid,'relation_id'=>$pid,'file_type'=>1])->get();
				return $this->success($project);
			}else{
				return $this->failed('pid不存在！');
			}	
		}
		
	}

	// 点击下一步按钮，将原数据存储到记录表  （测试的）
	public function store(Request $request){
		// $pid = $request->pid;
		// $res = Adjust::store($pid);
		$res = Adjust::updateData(548);
		dd($res);
	}

	// 修改项目页面   项目信息
	public function projectIndex(Request $request, $pid){
		if($pid){
			$i = $request->i ?? "";
			$res = true;
			if($i == ""){
				// 查看编辑后的内容，共用一个借口
				$res = Adjust::store($pid);		// 点击下一步，保存所有数据
			}
			if($res === true){
				$project_info = $this->projectservice->getProjectInfo($pid);
				//dump($project_info);exit;
				$project_info_array = collect($project_info)->toArray();//资源集合转数组
	        	// $project_info_array['plan'] = $this->projectservice->getPlans($pid);
	        	//dd(collect($project_info)->toArray());
				return $this->success($project_info_array);
			}
			return $this->failed($res);
		}else{
			return $this->failed('pid不存在！');
		}
	}

	// 节点信息
	public function planCustomIndex(Request $request, $pid){
		if($pid){
			$plan_custom = $this->projectservice->getPlans($pid);
			return $this->success($plan_custom);
		}else{
			return $this->failed('pid不存在！');
		}
	}

	// 修改提交操作
	public function projectEdit(Request $request, $pid, $status=1){ // status 为1 修改
		$user = Auth::guard('api')->user();
		$project = Project::with(['adjust'=>function($query)use($pid){
			$query->where('pid', $pid);
		}])->find($pid);
		// 阻止多次点击重复提交
		if($project->is_adjust != 0){
			return $this->failed('已提交调整');
		}elseif ($project->is_complete != 0) {
			return $this->failed('已提交完结');
		}
		$uid = $project->uid;
        //validate 验证
        $rules = [
        	'edit_reason' => 'required',
            'pname' => 'required'
        ];

        $message = [
        	'edit_reason.required' => '修改原因必须填写',
            'pname.required' => '项目名不能为空'
        ];
        //手动创建验证 request类不支持json 参数
        \Validator::make($request->all(), $rules,$message)->validate();

        $data = $request->all();
        // dump($data);exit;
        $data_array = json_decode($data['data'],true);
        // dd($data_array);
        $project_form = $data_array['form'];//获取表单基础数据
        if(!empty($project_form['xieban'])){
            //拼接数据 协办 按照旧数据格式
            $xieban ='';
            $xie_fuzhe = '';
            foreach ($project_form['xieban'] as $k=>$item){
                $xieban .=$item['cooprateCorp'].'|';
                $xie_fuzhe .=$item['cooprateMan'].'|';
            }
            $project_form['xieban'] = rtrim($xieban,'|');
            $project_form['xie_fuze'] = rtrim($xie_fuzhe,'|');
        }
        $project_form['is_adjust'] = $status;
        // dd($project_form);
        // dd($data_array['plan']);
       	$array_xieban = $project_form['cooprateCorps'];
       	unset($project_form['cooprateCorps']);
        $project_form['xieban'] = $this->projectservice->dataFormatXieban($array_xieban)['xieban'];
        $project_form['xie_fuze'] = $this->projectservice->dataFormatXieban($array_xieban)['xie_fuze'];

        try {
        	\DB::beginTransaction();

            Project::where('id',$pid)->update($project_form);
	        $plan_form_create = $this->projectservice->arrayToplan($data_array['plan'],$project);
	        // dd($plan_form_create);

	        $plan_custom_ids = ProjectPlanCustom::where('pid',$project->id)
                			->orderBy('id','asc')->pluck('id')->toArray(); //节点自增ID

            foreach ($plan_form_create as $k2=>$val){
                $save = $val; //插入或更新数据用
                unset($save['id']);
                $save['pid'] = $project->id;
                //如果id 存在数据库中 更新
                if(in_array($val['id'],$plan_custom_ids)){
                	for($i=1;$i<=12;$i++){
                		unset($save['content' . $i]);   // 删除月份节点任务更新，防止更新冲突
                	}
                	// \DB::connection()->enableQueryLog();  // 开启QueryLog
                    ProjectPlanCustom::where('id',$val['id'])->update($save);//更新节点
                    // dd(\DB::getQueryLog());
                }else{
                    ProjectPlanCustom::create($save); //新增
                }

            }

            if($project->adjust){
				// 驳回重新申报
				$adjust = Adjust::find($project->adjust->id);
				$adjust->pid = $pid;
				$adjust->edit_reason = $request->edit_reason;
	            $adjust->is_adjust = $status;
	            $adjust->save();
			}else{
				// 第一次提交
				// 修改原因插入adjust表
	            $adjust = new Adjust();
	            $adjust->pid = $pid;
	            $adjust->edit_reason = $request->edit_reason;
	            $adjust->is_adjust = $status;
	            $adjust->type = $project->type;
	            $adjust->save();
			}	

			// 上传附件
			$relation_id = $pid;
			$uid = $project->uid;
			$file_type = 1;
			$fileList=$request->fileList ?? '';
	        $fileList=json_decode($fileList);  //附件
	        if(!empty($fileList)){
				Upload::upload($pid,$relation_id,$uid,$file_type,$fileList);
	        }

			// 接入工作流
			$data_work = [
                'wf_type' => 'adjust', //业务表
                'wf_fid' => $adjust->id,//业务表主键ID
                'wf_id' => $project->wf_id,//流程表主键id
                'new_type' => '0',//紧急程度
                'check_con' => 'pass',//审核意见
            ];

			$flow = Workflow::startworkflow($data_work, $uid);
			if($flow['code'] == 1){
				$flow['adjust_id'] = $adjust->id;
				// 系统默认审核科室步骤
				// 直接五化办审核
				Adjust::autoCheck($adjust->id, $flow['run_id'], $project->wf_id);
				\DB::commit();
			}else{
				\Db::rollback();
				return $this->failed('提交失败！');
			}
			return $this->success($flow);
        }catch (\Exception $e) {
            \DB::rollback();
            return $this->failed($e->getMessage());
        }
	}

	/**
	 * 审核操作
	 * @param int $id adjust调整表主键id
	 **/
	public function adjustCheck(Request $request, $id){
		$uid = $request->uid;
		$role = $request->role;
		$check_con = $request->check_con ?? '通过'; //审核/驳回意见
		$wf_backflow = $request->wf_backflow??'';//模拟退回到科室 2 flow_process ID
		try{
			// 开启事务
       		\DB::beginTransaction();

			$adjust = Adjust::with('project:id,wf_id,is_adjust')->find($id);
			// dd($adjust);

			$workflow = Workflow::workflowInfo($id,'adjust',['uid'=>$uid,'role'=>$role]);
			// dd($workflow);


			// 判断重复审核提交
			if($workflow['run_id']){
				$message = Workflow::workflowCheck($workflow,$uid,$role);//后台验证
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

		            $project = Project::find($adjust->project->id);
		            $project->is_adjust = 0;
		            $project->save();
		        }else{
		            $submit_to_save='ok';
		            $backflow_id='';
		        }
				$save =[
		            "wf_title" =>  "",
		            "wf_fid" =>  $id,  // 调整表主键id
		            "wf_type" =>  'adjust',
		            "flow_id" =>  $adjust->project->wf_id,
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
		        // 审核成功后，判断是否是修改项目
		        Adjust::nextStep($id);
		        \DB::commit();
		        return $this->success('操作成功');
		    }
        }catch (\Exception $e) {
        	\Db::rollback();
            return $this->failed($e->getMessage());
        }
	}

	// 调整中项目列表
	public function adjustIng(Request $request){
		$is_adjust = $request->is_status ?? 1;  // 1修改 2停建
		$paginate = $request->paginate ?? 8;
	    $res = Adjust::with('project:id,type,pname,units_id,pro_status,fen_uid,year,is_year,progress')->whereHas('project', function($query)use($request){
	    	return $query->filter($request->except('status'));
	    })->where(['status_flow'=>1, 'is_adjust'=>$is_adjust])->paginate($paginate);
	    foreach ($res as $k=>$v){
	        $v->project->typename = Type::get_type_name($v->project->type);
	        $v->project->units_name = Unit::getName($v->project->units_id);
	        $v->project->flow_name = User::getUserName($v->project->fen_uid);
	        $v->time = date('Y-m-d', $v->uptime);
        }
	    return $this->success($res);
	}

    //	已调整列表
    public function adjustPass(Request $request){
    	$is_adjust = $request->is_status ?? 1;  // 1修改 2停建
    	$paginate = $request->paginate ?? 8;
    	$res = Adjust::with('project:id,type,pname,units_id,pro_status,fen_uid,year,is_year,progress')->whereHas('project', function($query)use($request){
    		return $query->filter($request->except('status'));
    	})->where(['status_flow'=>2, 'is_adjust'=>$is_adjust])->paginate($paginate);
        foreach ($res as $k=>$v){
            $v->project->typename = Type::get_type_name($v->project->type);
            $v->project->units_name = Unit::getName($v->project->units_id);
            $v->project->flow_name = User::getUserName($v->project->fen_uid);
            $v->time = date('Y-m-d', $v->uptime);
        }
        return $this->success($res);
    }

    // 修改申请单
    public function editLists(Request $request){
    	$is_adjust = $request->is_status ?? 1;  // 1修改 2停建
    	$paginate = $request->paginate ?? 8;
    	$res = Adjust::with('project:id,type,pname,units_id,pro_status,fen_uid,year,is_year,progress')->whereHas('project', function($query)use($request){
    		return $query->filter($request->except('status'));
    	})->where(['is_adjust'=>$is_adjust])->paginate($paginate);
    	// dd($res);
        foreach ($res as $k=>$v){
            $v->project->typename = Type::get_type_name($v->project->type);
            $v->project->units_name = Unit::getName($v->project->units_id);
            $v->project->flow_name = User::getUserName($v->project->fen_uid);
            $v->time = date('Y-m-d', $v->uptime);
        }
        return $this->success($res);
    }

    // 停建申请单
    public function stopLists(Request $request){
    	$is_adjust = $request->is_status ?? 2;  // 1修改 2停建
    	$paginate = $request->paginate ?? 8;
    	$res = Adjust::with('project:id,type,pname,units_id,pro_status,fen_uid,year,is_year,progress')->whereHas('project', function($query)use($request){
    		return $query->filter($request->except('status'));
    	})->where(['is_adjust'=>$is_adjust])->paginate($paginate);
        foreach ($res as $k=>$v){
            $v->project->typename = Type::get_type_name($v->project->type);
            $v->project->units_name = Unit::getName($v->project->units_id);
            $v->project->flow_name = User::getUserName($v->project->fen_uid);
            $v->time = date('Y-m-d', $v->uptime);
        }
        return $this->success($res);
    }

    // 待审核列表
    public function adjustDsh(Request $request){
    	$is_adjust = $request->is_status ?? 1;  // 1修改 2停建
    	$paginate = $request->paginate ?? 8;
    	$uid = $request->uid;
    	$role_id = $request->role_id;
        if(empty($uid)){
            return $this->failed('操作失败');
        }
        
        if($role_id =='2'){ //业务科室
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

        $flow_id = FlowProcess::getFlowProcessId($uid, $process_name);
        $from_id = Run::getDshPId($flow_id, 'adjust');  // 待当前用户审核的列表
        // dd($from_id);
        $arr = [];
        foreach ($from_id as $key => $value) {
        	$arr[] = $value['from_id'];
        }
        // 查找对应的项目id
        $res = Adjust::with('project:id,type,pname,units_id,pro_status,fen_uid,year,is_year,progress')->whereHas('project',function($query)use($request){
        	return $query->filter($request->except('status'));
        })->where(['status_flow' => 1, 'is_adjust'=>$is_adjust])->whereIn('id',$arr)->paginate($paginate);
        foreach ($res as $k=>$v){
            $v->project->typename = Type::get_type_name($v->project->type);
            $v->project->units_name = Unit::getName($v->project->units_id);
            $v->project->flow_name = User::getUserName($v->project->fen_uid);
            $v->time = date('Y-m-d', $v->uptime);
        }
        return $this->success($res);
    }

    /**
     * 修改进度，查看进度
     * @param int $customid 节点id
     * @param int $month    月份
     **/
    public function progress(Request $request, $customid, $month){
    	// dd($request->method());
    	if($request->method() == "GET"){
    		$progress = Progress::getProgress($customid, $month);
    		if(!$progress){
    			// 没有填写进度，就查看节点计划
    			$progress = ProjectPlanCustom::getContent($customid, $month);
    			$progress['tag'] = 1;  // 无进度的情况
    			return $this->success($progress);
    		}    		
	    	$count = count($progress);
	    	$project = [];
	    	foreach ($progress as $key => $value) {
	    		$project = $value['project'];
	    		unset($progress[$key]['project']);
	    		if($value['p_status'] !=5){
	    			$progress[$key]['count'] = $count--;
	    		}
	    		if($value['y_time'] < 0){
	    			$progress[$key]['y_time'] = "逾期" . trim($value['y_time'], '-') . "天";
	    		} elseif ($value['y_time'] > 0) {
	    			$progress[$key]['y_time'] = "提前" . $value['y_time'] . "天";
	    		}
	    		if($value['p_status'] == 1){
	    			$progress[$key]['percentage'] = "25%";
	    		}elseif($value['p_status'] == 2){
	    			$progress[$key]['percentage'] = "50%";
	    		}elseif($value['p_status'] == 3){
	    			$progress[$key]['percentage'] = "75%";
	    		}elseif($value['p_status'] == 4){
	    			$progress[$key]['percentage'] = "100%";
	    		}
	    		$progress[$key]['fileList'] = Upload::where(['pid'=>$value['pid'],'relation_id'=>$value['id'],'file_type'=>2])->get();
	    	}
	    	$res = [];
	    	$res['tag'] = 2;  // 填写进度的情况
	    	$res['project'] = $project;
	    	$res['progress'] = $progress;
	    	return $this->success($res);
    	}elseif($request->method() == "POST"){
    		$flag = $request->flag;
    		$pid = $request->pid;
    		$tag = $request->tag;
    		// flag为1修改
	    	if($flag == 1){	
	    		$content = $request->contents;         // 修改计划任务
	    		try {
	    			\DB::beginTransaction();
	    			if($tag == 1){
	    				// 没有填写进度，只改当前计划
	    				ProjectPlanCustom::updateContent($customid, $month, $content);
	    			}elseif($tag == 2){
	    				$progress_id = $request->progress_id;          // 当前进度id
	    				$a_progress = $request->a_progress;            // 实际计划进度
		    			$progress = Progress::find($progress_id);
		    			$progress->a_progress = $a_progress;
		    			$progress->save();
		    			ProjectPlanCustom::updateContent($customid, $month, $content);

		    			// 上传附件
		    			$relation_id = $progress_id;
		    			$uid = Project::find($pid)->uid;
		    			$file_type = 2;
    					$fileList=$request->fileList ?? '';
				        $fileList=json_decode($fileList);  //附件
				        if(!empty($fileList)){
							Upload::upload($pid,$relation_id,$uid,$file_type,$fileList);
				        }
	    			}
	   
    				\DB::commit();
    				return $this->success('修改成功');   			
	    		} catch (\Exception $e) {
	    			\DB::rollback();
	    			return $this->failed($e->getMessage());
	    		}
	    	} elseif ($flag ==2) {
	    		// flag为2撤销计划
	    		// 清除所有进度，谨慎使用    		
	    		try{
	    			\DB::beginTransaction();
	    			// 软删除该节点在该月的所有进度
	    			Progress::softDel($pid, $customid, $month);
	    			// 清空该节点在该月的节点计划内容
	    			ProjectPlanCustom::clearContent($customid, $month);
	    			\DB::commit();
	    			return $this->success('修改成功');
	    		} catch (\Exception $e) {
	    			\DB::rollback();
	    			return $this->failed($e->getMessage());
	    		}
	    	}
    	}
    	
    }

    // 编辑前内容，历史记录，项目信息和节点信息
    public function history(Request $request){
    	$pid = $request->pid;
    	if($pid){
    		// 获取编辑前一条记录
	    	$flag = ProjectHistory::getFlag($pid);

		    $res = ProjectHistory::getProjectHistoryInfo($pid, $flag);

		    return $this->success($res);
    	}
    	return $this->failed("pid不存在！");
    }

    // 编辑前内容，历史记录，项目进度信息
    public function progress_history(Request $request){
    	$customid = $request->customid;
    	$month = $request->month;
    	$flag = $request->flag;
    	if($customid && $month){
    		$progress = ProjectProgressHistory::getHistoryProgress($customid, $month, $flag);
    		if($progress){   		
    			$count = count($progress);
		    	$project = [];
		    	foreach ($progress as $key => $value) {
		    		$project = $value['project'];
	    			unset($progress[$key]['project']);
		    		if($value['p_status'] !=5){
		    			$progress[$key]['count'] = $count--;
		    		}
		    		if($value['y_time'] < 0){
		    			$progress[$key]['y_time'] = "逾期" . trim($value['y_time'], '-') . "天";
		    		} elseif ($value['y_time'] > 0) {
		    			$progress[$key]['y_time'] = "提前" . $value['y_time'] . "天";
		    		}
		    		if($value['p_status'] == 1){
		    			$progress[$key]['percentage'] = "25%";
		    		}elseif($value['p_status'] == 2){
		    			$progress[$key]['percentage'] = "50%";
		    		}elseif($value['p_status'] == 3){
		    			$progress[$key]['percentage'] = "75%";
		    		}elseif($value['p_status'] == 4){
		    			$progress[$key]['percentage'] = "100%";
		    		}
		    	}
    			$res['tag'] = 2;
		    	$res['project'] = $project;
		    	$res['progress'] = $progress;
    			// 已经填了进度信息的，就显示进度信息	
    			return $this->success($res);
    		}
    		$res = [];
    		// 没有填进度信息的，就显示节点计划内容
    		$res = ProjectPlanCustomHistory::getHistoryContent($customid, $month, $flag);
    		$res['tag'] = 1;
    		return $this->success($res);
    	}
    	return $this->failed("操作失败！");
    }

    // 没有点击确定情况下，退回之前的数据。不保留新数据
    public function goBack(Request $request, $pid){
    	Adjust::updateData($pid);
    }

    // 删除附件
	public function delFile(Request $request){
		$file_id = $request->file_id;
		Upload::destroy($file_id);
		return $this->success(200);
	}

	// 上传附件
	public function uploadFile($pid,$relation_id,$uid,$file_type,$fileList){
		$pid = $request->pid;
		$relation_id = $request->relation_id;
		$uid = $request->uid;
		$file_type = $request->file_type;  // 1,申报 2，进度
		$fileList=$request->fileList ?? '';
        $fileList=json_decode($fileList);  //附件
        if(!empty($fileList)){
			Upload::upload($pid,$relation_id,$uid,$file_type,$fileList);
        }
	}

    // 数据迁移
    public function qianyi(){
    	$res = \DB::connection('xgwh_online')->table('wh_adjus')->orderBy('id','asc')->get(['id','ago_id','status','reasons','adjus_time'])->toArray();
    	// dd($res);
    	$data = [];
    	foreach ($res as $k => $v) {
    		$data[$k]['id'] = $v->id;
    		$data[$k]['pid'] = $v->ago_id;
    		$data[$k]['type'] = $v->type;
    		if(in_array($v->status,[0,1,2,3,4,5])){
    			$data[$k]['status_flow'] = 1;
    		}elseif(in_array($v->status, [11])){
    			$data[$k]['status_flow'] = -1;
    		}elseif(in_array($v->status, [6])){
    			$data[$k]['status_flow'] = 2;
    		}
    		$data[$k]['stop_reason'] = $v->reasons;
    		$data[$k]['is_adjust'] = 2;
    		$data[$k]['uptime'] = $v->adjus_time;
    		$data[$k]['created_at'] = date('Y-m-d H:i:s', $v->adjus_time);
    		$data[$k]['updated_at'] = date('Y-m-d H:i:s', $v->adjus_time);
    	}
    	\DB::table("adjust_copy")->insert($data);
    	return $this->success($res);
    }

    // 流程操作日志迁移
    public function a(){

    }
}