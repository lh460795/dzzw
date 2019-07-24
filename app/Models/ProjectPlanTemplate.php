<?php

namespace App\Models;


class ProjectPlanTemplate extends BaseModel
{
    //use SoftDeletes;

    protected $table = 'project_plan_templates';
    protected $fields_all;

    //TODO 迁移数据时关闭
    public $timestamps = false;



}
