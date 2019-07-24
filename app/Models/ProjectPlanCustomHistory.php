<?php

namespace App\Models;


class ProjectPlanCustomHistory extends BaseModel
{
    //use SoftDeletes;

    protected $table = 'project_plan_custom_history';
    protected $fields_all;
    //批量赋值字段
    protected $guarded = [];
    //TODO 迁移数据时关闭
    public $timestamps = false;

    // 节点计划内容
    public static function getHistoryContent($customid, $month, $flag){
    	return self::where(['id'=>$customid, 'flag'=>$flag])->first(['content' . $month])->toArray();
    }
}
