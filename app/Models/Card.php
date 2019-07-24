<?php

namespace App\Models;


class Card extends BaseModel
{
    //use SoftDeletes;

    protected $table = 'card';
    protected $fields_all;

    //TODO 迁移数据时关闭
    public $timestamps = false;

    //关联项目节点表 1对多
    public function plancustom()
    {
        return $this->hasMany('App\Models\ProjectPlanCustom','custom_id','id');
    }
}
