<?php

namespace App\Models;

use App\Models\User;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Work\Workflow;
use App\Work\Model\RunProcess;

class Complete extends BaseModel {
    protected $table = 'complete';

    protected $primaryKey = 'id';

    public function project(){
        return $this->belongsTo(Project::class, 'pid', 'id');
    }

    // 系统自动审核
    public static function autoCheck($id, $run_id, $wf_id){
        try {
            \DB::beginTransaction();
    		$run = RunProcess::findNextId($run_id);
    		$uid = (int)$run->sponsor_ids;
    		$workflow = Workflow::workflowInfo($id,'complete',['uid'=>$uid,'role'=>'']);
    		// dd($workflow);
    		$save =[
                "wf_title" =>  "",
                "wf_fid" =>  $id,  // 调整表主键id
                "wf_type" =>  'complete',
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
}