<?php

namespace App\Models;

use App\Models\User;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Work\Workflow;
use App\Work\Model\RunProcess;
use App\Models\Project;
use App\Models\ProjectHistory;
use App\Models\ProjectPlanCustom;
use App\Models\ProjectPlanCustomHistory;
use App\Models\Progress;
use App\Models\ProjectProgressHistory;
use App\Models\Upload;
use App\Models\UploadHistory;

class Adjust extends BaseModel {
	protected $table = 'adjust';

	protected $primaryKey = 'id';

    public $timestamps = true;

	public function project(){
		return $this->belongsTo(Project::class, 'pid', 'id');
	}

    // 1、停建通过审核之后，主表字段改为调整完成
    // 2、修改审核驳回之后，退回之前的数据
    public static function nextStep($adjust_id){
        $adjust = Adjust::find($adjust_id);
        // dd($adjust);
        if($adjust->is_adjust == 2 && $adjust->status_flow == 2){
            // 停建审核通过之后，更改主表pro_status字段
            return Project::where(['id'=>$adjust->pid])->update(['pro_status'=>5]);
        }

        // 修改驳回之后，退回之前的数据
        if($adjust->is_adjust == 1 && $adjust->status_flow == -1){
            return self::updateData($adjust->pid)===true ? true : dd(self::updateData($adjust->pid));
        }
    }

	/**
	 * 修改提交操作，只走五化办流程，科室审核步骤由系统代替审核
	 **/
	public static function autoCheck($id, $run_id, $wf_id){
        try {
            \DB::beginTransaction();
    		$run = RunProcess::findNextId($run_id);
    		$uid = (int)$run->sponsor_ids;
    		$workflow = Workflow::workflowInfo($id,'adjust',['uid'=>$uid,'role'=>'']);
    		// dd($workflow);
    		$save =[
                "wf_title" =>  "",
                "wf_fid" =>  $id,  // 调整表主键id
                "wf_type" =>  'adjust',
                "flow_id" =>  $wf_id,
                "flow_process" =>  $workflow['flow_process'],
                "run_id" =>  $workflow['run_id'],
                "run_process" =>  $workflow['run_process'],
                "npid" =>  $workflow['nexprocess']->id ?? "",
                "wf_mode" =>  $workflow['wf_mode'],
                "sup" => "",
                "check_con" =>  "default",
                "wf_backflow" => "",
                "btodo" => "",
                "wf_singflow" =>  "",
                "submit_to_save" =>  'default', //
                "art" =>  "",
                "sing_st"=>  $workflow['sing_st']
            ];

        	$result = Workflow::workdoaction($save, $uid);//获取下一步骤信息 根据按钮值 做提交 或者回退处理
            \DB::commit();
        	return true;
        } catch (\Exception $e) {
            \DB::rollback();
            return $e->getMessage();
        }
	}

    /**
     * 点击下一步按钮，将原数据存储到记录表
     **/
    public static function store($pid){
        try {
            \DB::beginTransaction();
            $project = Project::find($pid)->toArray();
            $project_plan = ProjectPlanCustom::where(['pid'=>$pid])->get()->toArray();
            $project_progress = Progress::where(['pid'=>$pid])->get()->toArray();
            $data1 = ProjectHistory::orderBy('flag', 'desc')->find($pid);
            // dd($data1);
            if(!$data1){
                $res1 = ProjectHistory::insert($project);
            }else{
                $project['flag'] = $data1->flag+1;
                $res1 = ProjectHistory::insert($project);
            }

            $data2 = ProjectPlanCustomHistory::where(['pid'=>$pid])->orderBy('flag','desc')->first();
            // dd($data2);
            if(!$data2){
                $res2 = ProjectPlanCustomHistory::insert($project_plan);
            }else{
                foreach ($project_plan as $key => $value) {
                    $project_plan[$key]['flag'] = $data1->flag + 1; 
                }
                $res2 = ProjectPlanCustomHistory::insert($project_plan);
            }

            $data3 = ProjectProgressHistory::where(['pid'=>$pid])->orderBy('flag','desc')->first();
            if(!$data3){
                $res3 = ProjectProgressHistory::insert($project_progress);
            }else{
                foreach ($project_progress as $key => $value) {
                    $project_progress[$key]['flag'] = $data1->flag + 1;
                }
                $res3 = ProjectProgressHistory::insert($project_progress);
            }

            // 上传附件
            $file = Upload::where('pid', $pid)->whereIn('file_type', [1, 2])->get()->toArray();
            $file_history = UploadHistory::where('pid', $pid)->orderBy('flag','desc')->first();
            if(!$file_history){
                UploadHistory::insert($file);
            }else{
                foreach ($file as $key => $value) {
                    $file[$key]['flag'] = $data1->flag +1;
                }
                UploadHistory::insert($file);
            }

            \DB::commit();
            return true;

        } catch (\Exception $e) {
            \DB::rollback();
            return $e->getMessage();
        }
    }


    /**
     *  回退操作
     *  审核不通过之后，回退到之前的数据
     *  1、取出老数据
     *  2、替换当前数据，回退到没有更改前的数据
     **/
    public static function updateData($pid){
        try {
            // 开启事务
            \DB::beginTransaction();

            $project_history = ProjectHistory::where('id', $pid)->orderBy('flag', 'desc')->first()->toArray();
            $flag = $project_history['flag'];
            $project_history_id = $project_history['id']; // 将id存入变量
            // 删掉主表id
            // unset($project['id']);
            // 删掉id和old_id
            unset($project_history['id']);
            unset($project_history['flag']);
            // 更新主表，新数据替换旧数据
            $res1 = Project::where(['id' => $pid])->update($project_history);
            // 更新project_history表，将主表数据更新到project_history表，成为记录，以便后期查看
            // $res2 = ProjectHistory::where(['id' => $project_history_id])->update($project);

            // 主表节点
            $project_plan_custom = ProjectPlanCustom::where(['pid' => $pid])->get()->toArray();
            // 将id存入变量
            $project_plan_custom_id = [];
            foreach ($project_plan_custom as $key => $value) {
                $project_plan_custom_id[$key] = $value['id'];
                // 销毁id  pid项目不变
                unset($project_plan_custom[$key]['id']);
            }
            // project_history记录表节点
            $project_plan_custom_history = ProjectPlanCustomHistory::where(['pid' => $pid, 'flag' => $flag])->get()->toArray();
            foreach ($project_plan_custom_history as $key => $value) {
                $project_plan_custom_history_id[$key] = $value['id'];
                // 销毁id  pid项目不变
                unset($project_plan_custom_history[$key]['id']);
                unset($project_plan_custom_history[$key]['flag']);
            }
            // dump($project_plan_custom_id);
            // dump($project_plan_custom_history_id);
            // 更新主表，新数据替换旧数据  节点替换   将project_history表数据更新到project表
            foreach ($project_plan_custom_id as $key => $value) {
                $data1 = ProjectPlanCustom::where(['id' => $value])->update($project_plan_custom_history[$key]);
            }

            // 更新记录表，旧数据存入  节点替换     将project表数据存入project_history表，当做记录查看
            // foreach ($project_plan_custom_history_id as $key => $value) {
            //     $data2 = ProjectPlanCustomHistory::where(['id' => $value])->update($project_plan_custom[$key]);
            // }

            // 主表进度查询
            $project_progress = Progress::where(['pid' => $pid])->get()->toArray();
            // dd($project_progress);
            // 将id存入变量
            $project_progress_id = [];
            foreach ($project_progress as $key => $value) {
                $project_progress_id[$key] = $value['id'];
                // 删除id
                unset($project_progress[$key]['id']);
            }

            // 记录表进度查询
            $project_progress_history = ProjectProgressHistory::where(['pid' => $pid, 'flag' => $flag])->get()->toArray();
            // dd($project_progress_history);
            // 将id存入变量
            foreach ($project_progress_history as $key => $value) {
                $project_progress_history_id[$key] = $value['id'];
                // 删除id
                unset($project_progress_history[$key]['id']);
                unset($project_progress_history[$key]['flag']);
            }

    // dump($project_progress);
            // 循环更新主表进度数据 将project_progress_history表数据更新到project_progress
            foreach ($project_progress_id as $key => $value) {
                $data3 = Progress::where(['id' => $value])->update($project_progress_history[$key]);
            }

            // 将主表进度数据放入project_progress_history当做记录信息查询
            // foreach ($project_progress_history_id as $key => $value) {
            //     $data4 = ProjectProgressHistory::where(['id' => $value])->update($project_progress[$key]);
            // }

            // dd($project_plan_custom_history);
            // dump($project_plan_custom);
            // dd($project_plan_custom_history);

            // 回退附件
            $file = Upload::where('pid',$pid)->whereIn('file_type',[1,2])->get()->toArray();
            $file_id = [];
            foreach ($file as $key => $value) {
                $file_id[$key] = $value['id'];
                unset($file[$key]['id']);
            }
            $file_history = UploadHistory::where(['pid' => $pid, 'flag' => $flag])->get()->toArray();
            foreach ($file_history as $key => $value) {
                $file_history_id[$key] = $value['id'];
                unset($file_history[$key]['id']); 
                unset($file_history[$key]['flag']); 
            }
            foreach ($file_id as $key => $value) {
                Upload::where(['id'=>$value])->update($file_history[$key]);
            }

            \DB::commit();
            return true;
        } catch (\Exception $e) {
            \DB::rollback();
            return $e->getMessage();
        }
    }
}