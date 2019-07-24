<?php

namespace App\Models;


class ProjectProgressHistory extends BaseModel {
    //use SoftDeletes;

    protected $table = 'project_progress_history';
    protected $fields_all;
    //批量赋值字段
    protected $guarded = [];

    //TODO 迁移数据时关闭
    public $timestamps = false;

    public function progressRes(){
    	return $this->belongsTo('App\Models\ProjectPlanCustomHistory', 'custom_id', 'id');
    }

    public function project(){
        return $this->belongsTo('App\Models\ProjectHistory', 'pid', 'id');
    }

    // 历史进度查看
    public static function getHistoryProgress($customid, $month, $flag){
        return  self::with(['progressRes'=>function($query)use($flag,$month){
                    $query->where('flag',$flag)->select('id','p_value','m_value','content' . $month);
                }, 'project'=>function($query)use($flag){
                    $query->where('flag',$flag)->select('id','pname');
                }])->where(['custom_id'=>$customid, 'month'=>$month, 'flag'=>$flag])
                    ->orderBy('id','desc')->get()->toArray();
    }



}
