<?php

namespace App\Models;


class Todo extends BaseModel
{

    protected $table = 'todos';
    protected $fields_all;

    //TODO 迁移数据时关闭
    public $timestamps = false;

}
