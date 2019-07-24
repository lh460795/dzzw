<?php

namespace App\Models;

use App\Models\ProjectPlanCustom;
use Illuminate\Database\Eloquent\SoftDeletes;


class Progress extends BaseModel
{
    use SoftDeletes;

    protected $table = 'project_progress';
    protected $fields_all;
//    protected $hidden = ['created_at','updated_at','deleted_at'];
    //批量赋值字段
    protected $fillable = [
        'pid','p_time','p_time_old','y_time','custom_id','month','p_year','p_progress',
        'a_progress','explain','remark','p_status','m_account','created_at','updated_at','deleted_at',
        'y_time'
    ];
    //TODO 迁移数据时关闭
    public $timestamps = false;

    protected $dates = ['deleted_at'];   // 软删除

    public function progressRes()
    {
        return $this->belongsTo('App\Models\ProjectPlanCustom', 'custom_id', 'id');
    }

    public function project(){
        return $this->belongsTo('App\Models\Project', 'pid', 'id');
    }


    // 进度查看
    public static function getProgress($customid, $month){
       return self::with('progressRes:id,p_value,m_value,content' . $month, 'project:id,pname')
                ->where(['custom_id'=>$customid,'month'=>$month])
                ->orderBy('id','desc')->get()->toArray();
    }

    // 软删除。撤销节点，删除所有进度
    public static function softDel($pid, $custom_id, $month){
        return self::where(['pid'=>$pid, 'custom_id'=>$custom_id, 'month'=>$month])->delete();
    }
}
