<?php
namespace App\Work\Model;

use Illuminate\Database\Eloquent\Model;


class Run extends Model{
    protected $table = 'wf_run';
    protected $guarded = [];


//    public function projects()
//    {
//        //第三个参数表示中间模型的外键名
//        //第四个参数表示最终模型的外键名
//        //第五个参数表示本地键名
//        //而第六个参数表示中间模型的本地键名
//        return $this->hasManyThrough(
//            'App\Work\Model\RunProcess',
//            'App\Models\Project',
//            'id', // 中间表外键...
//            'run_id', // 文章表外键...
//            'id', // 国家表本地键...
//            'id' // 用户表本地键...
//        );

    public function process(){

        return $this->hasMany('App\Work\Model\RunProcess','run_id','id');
    }

    public function projects(){

        return $this->belongsTo('App\Models\Project', 'from_id', 'id');
    }
    public function natong(){

        return $this->belongsTo('App\Models\natong', 'from_id', 'pid');
    }
    //通过uid 业务表名称 找到流程ID
    public static function getflow_id($uid,$from_table){
        return self::where([['uid', '=', $uid],['from_table', '=', $from_table]])->select('flow_id')->first();
    }

    // 通过步骤id，找到待审核的项目id
    public static function getDshPId($arr, $from_table){
        return self::where(['from_table'=>$from_table,'status'=>0])->whereIn('run_flow_process',$arr)->get(['from_id'])->toArray();
    }
}