<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
class ProjectPlanCustomSave extends BaseModel
{
    use SoftDeletes;

    protected $datas = ['deleted_at'];
    protected $table = 'project_plan_custom_save';
    protected $fields_all;

    //批量赋值字段
    protected $fillable = [
        'pid', 'p_name','p_value','m_name','m_value','m_zrdw','p_year','p_month',
        'content1','content2','content3','content4','content5','content6','content7',
        'content8','content9','content10','content11','content12'
    ];
    //TODO 迁移数据时关闭
    public $timestamps = true;

}
