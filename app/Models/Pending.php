<?php

namespace App\Models;


class Pending extends BaseModel
{

    protected $table = 'pendings';
    protected $fields_all;

    //TODO 迁移数据时关闭
    public $timestamps = false;

}
