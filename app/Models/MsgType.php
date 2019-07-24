<?php

namespace App\Models;


class MsgType extends BaseModel
{
    //use SoftDeletes;

    protected $table = 'msg_type';
    protected $fields_all;

    //TODO 迁移数据时关闭
    public $timestamps = false;



}
