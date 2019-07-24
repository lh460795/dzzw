<?php

namespace App\Models;

use App\Models\User;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use App\Http\Resources\Api\V1\Frontend\ProjectHistoryResource;

class ProjectHistory extends BaseModel
{
    use SoftDeletes, Notifiable,Filterable;
    protected $table = 'project_history';
    protected $fields_all;

    protected $guarded = [];

    public $timestamps = false;

    public function planCustomHistory(){
    	return $this->hasMany('App\Models\ProjectPlanCustomHistory', 'pid', 'id');
    }

    public static function getFlag($pid){
    	return self::where(['id'=>$pid])->orderBy('flag','desc')->value('flag');
    }

    // 历史记录表，历史记录信息
    public static function getProjectHistoryInfo($pid, $flag){
    	return new ProjectHistoryResource(self::where(['id'=>$pid, 'flag'=>$flag])->first());
    }
}