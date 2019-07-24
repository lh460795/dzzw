<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use EloquentFilter\Filterable;

class UserLive extends BaseModel
{
    //use SoftDeletes;
    use Filterable;

    protected $fields_all;

    //TODO 迁移数据时关闭
    public $timestamps = false;

    protected $table='user_lives';

    public function unit(){

        return $this->hasOne('App\models\Unit','id','units_id');

    }

    public function area(){

        return $this->hasOne('App\models\Area','id','district_id');

    }
}
