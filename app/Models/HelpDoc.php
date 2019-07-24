<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpDoc extends BaseModel
{
    //use SoftDeletes;

    protected $table = 'help_doc';
    protected $fields_all;

    //TODO 迁移数据时关闭
    public $timestamps = false;



}
