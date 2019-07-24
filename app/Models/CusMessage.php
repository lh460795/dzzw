<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Frontend\ActionButtonTrait;
class CusMessage extends BaseModel
{
    //use SoftDeletes;
    use ActionButtonTrait;

    protected $table = 'custom_message';
    protected $fields_all;

    //TODO 迁移数据时关闭
    public $timestamps = false;



}
