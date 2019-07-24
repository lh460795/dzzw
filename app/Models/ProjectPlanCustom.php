<?php

namespace App\Models;


class ProjectPlanCustom extends BaseModel
{
    //use SoftDeletes;

    protected $table = 'project_plan_custom';
    protected $fields_all;
    protected $hidden = ['created_at','updated_at','deleted_at'];
    //批量赋值字段
    protected $fillable = [
        'pid', 'p_name','p_value','m_name','m_value','m_zrdw','p_year','p_month',
        'content1','content2','content3','content4','content5','content6','content7',
        'content8','content9','content10','content11','content12','created_at','updated_at','deleted_at',
        'id'
    ];
    //TODO 迁移数据时关闭
    public $timestamps = false;

    //通过当前用户id 以及步骤名称 获取步骤ID
    public static function getProjectAccount($custom_id)
    {
        return self::where('id', $custom_id)->first();
    }

    public static function projectInfo($custom_id)
    {
        $flow_process = Project::where('id', $custom_id)->get();
        return $flow_process;
    }

    public function projectRes()
    {
        return $this->belongsTo('App\Models\Project', 'pid', 'id');
    }

    public function customRes()
    {
        return $this->hasMany('App\Models\Progress','custom_id','id');
    }

    // 根据节点，根据月份，获取月份填写的计划内容，当没有填写进度，可以查看计划内容
    public static function getContent($customid, $month){
        return self::where(['id'=>$customid])->first(['content' . $month])->toArray();
    }

    // 撤销节点，清空节点计划内容
    public static function clearContent($customid, $month){
        return self::where(['id'=>$customid])->update(['content' . $month => '']);
    }

    // 修改计划任务
    public static function updateContent($customid, $month, $content){
        return self::where(['id'=>$customid])->update(['content' . $month => $content]);
    }

}
