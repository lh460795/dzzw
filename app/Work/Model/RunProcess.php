<?php
namespace App\Work\Model;

use Illuminate\Database\Eloquent\Model;


class RunProcess extends Model{
    protected $table = 'wf_run_process';
    protected $guarded = [];

    public function runs(){

        return $this->belongsTo('App\Work\Model\Run', 'run_id', 'id');
    }

    // 阻止多次点击提交
    public static function checkRepeat($run_id, $uid){
        // dd($uid);
        return self::where(['run_id' => $run_id, 'sponsor_ids' => $uid])->whereNotNull('remark')->value('status');
    }

    //找到下一步审核人的id
    public static function findNextId($run_id){
        return self::where(['run_id' => $run_id])->orderBy('id', 'asc')->first(['sponsor_ids']);  //取得第一条科室
    }

    // 审核操作记录，当前操作记录人名
    public static function getUserName($run_process){
        return self::where(['id' => $run_process])->value('sponsor_text');
    }
}