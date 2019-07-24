<?php
namespace App\Work\Model;

use Illuminate\Database\Eloquent\Model;
use App\Work\Model\FlowProcess;
use App\Work\Model\RunProcess;
use App\Models\User;

class RunLog extends Model{
    protected $table = 'wf_run_log';
    protected $guarded = [];

    /**
     * 审核意见
	 * @param $form_id 主键id
	 * @param $from_table 关联的表名
     **/
    public static function log($from_id,$from_table){
    	// default系统默认审核通过的，不用展示
    	$log = self::where(['from_id'=>$from_id,'from_table'=>$from_table, ['btn','<>','default']])
    			->get(['id','uid','content','btn','created_at','run_process','flow_process'])->toArray();
                
    	if($log){
    		foreach ($log as $key => $value) {
    		    if($value['flow_process'] == 0)
    		    {
                    $log[$key]['content']='';
                    //该记录用户角色名
                    $log[$key]['rolename'] = '立项单位';
                    //该记录用户名
                    $log[$key]['username'] = User::getUserName($value['uid']);

                    $log[$key]['btn'] = self::getBtn($value['btn'], $log[$key]['rolename']);
                }else{
                    //该记录用户角色名
                    $log[$key]['rolename'] = FlowProcess::getRoleName($value['flow_process']);
                    //该记录用户名
                    $log[$key]['username'] = RunProcess::getUserName($value['run_process']);
                    
                    $log[$key]['btn'] = self::getBtn($value['btn'], $log[$key]['rolename']);
                }
    		}
    		foreach ($log as $k=>$v)
    		{
    		    if(in_array($v['rolename'],['科室','副秘书长','五化办']))
    		    {
                    $log[$k]['content']='';
                }
            }
    	}

    	return $log;
    }

    //返回操作日志 通过 驳回 送审
    protected static function getBtn($btn,$role=''){
        $ok = '通过';
        if($role =='五化办'){
            $ok = '送审';
        }else{
            $ok = '通过';
        }
        $type = ['Send'=>'申报','ok'=>$ok,'Back'=>'驳回','SupEnd'=>'终止流程',
            'Sing'=>'会签提交','sok'=>'会签同意','SingBack'=>'会签退回','SingSing'=>'会签再会签'];
        return $type[$btn] ?? '';
    }

}