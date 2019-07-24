<?php
namespace App\Work\Model;

use Illuminate\Database\Eloquent\Model;


class FlowProcess extends Model{
    protected $table = 'wf_flow_process';
    protected $guarded = [];

    //通过流程ID 以及步骤名称 获取步骤ID
    public static function getprocessId($flow_id,$process_name){
        return self::where([['flow_id', '=', $flow_id],['process_name', '=', $process_name]])->select('id')->first();
    }
    //通过当前用户id 以及步骤名称 获取步骤ID
    public static function getprocessIdByuid($uid,$process_name){
        return self::where([['auto_sponsor_ids', '=', $uid],['process_name', '=', $process_name]])->select('id')->first();
    }
    // 通过当前用户id 以及步骤名称 获取步骤ID集合
    public static function getprocessIdsByflow_id($uid,$process_name){
        $flow_info = self::where([['auto_sponsor_ids', '=', $uid],['process_name', '=', $process_name]])->select('id','flow_id')->first();

        return self::where([['flow_id', '=', $flow_info->flow_id],['id', '>', $flow_info->id]])->select('id')->pluck('id');
    }
    // 通过当前用户id 以及步骤名称 获取当前流程所有步骤ID
    public static function getprocessIdsAll($uid,$process_name){
        $flow_info = self::where([['auto_sponsor_ids', '=', $uid],['process_name', '=', $process_name]])->select('id','flow_id')->first();

        return self::where('flow_id', '=', $flow_info->flow_id)->select('id')->pluck('id');
    }

    // 获得角色名process_name
    public static function getRoleName($flow_process){
        return self::where(['id' => $flow_process])->value('process_name');
    } 

    // 通过当前用户id，获取当前步骤所有id
    public static function getFlowProcessId($uid, $process_name){
        $arr = self::where(['auto_sponsor_ids'=>$uid, 'process_name'=>$process_name])->get(['id'])->toArray();
        $res = [];
        foreach ($arr as $key => $value) {
            $res[] = $value['id'];
        }
        return $res;
    }

    //通过当前角色 和步骤名称 获取指定步骤ID
    public static function getprocessIdByname($uid,$process_name_now,$process_name_next){
        $flow_info = self::where([['auto_sponsor_ids', '=', $uid],['process_name', '=', $process_name_now]])->select('id','flow_id')->first();

        return self::where([['flow_id', '=', $flow_info->flow_id],['process_name', '=', $process_name_next]])->value('id');
    }

    // 获得角色名process_name
    public static function getRunFlowProcess($from_table,$from_id){
        $run_flow_process = self::where(['from_table' => $from_table,'from_id'=>$from_id])->value('run_flow_process');
        return self::where(['id'=>$run_flow_process])->value('sponsor_ids');
    }
}