<?php

namespace App\Models;


class Area extends BaseModel
{
    //use SoftDeletes;

    protected $table = 'area';
    protected $fields_all;

    //TODO 迁移数据时关闭
    public $timestamps = false;

}
