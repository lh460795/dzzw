<?php
namespace App\Work\Model;

use Illuminate\Database\Eloquent\Model;


class Flow extends Model{
    protected $table = 'wf_flow';
    protected $guarded = [];

    // 获取分管领导
    public static function getFlowName($flow_id){
    	return self::where(['id' => $flow_id])->value('flow_name');
    }

}