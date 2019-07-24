<?php

namespace App\Models;

use App\Models\User;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
class Project extends BaseModel
{
    const PROJECT_NORMAL = 0;
    const PROJECT_SLOW  = 1;
    const PROJECT_DELAY = 2;
    const PROJECT_OVERDUE = 3;
    const PROJECT_COMPLETE = 4;
    const PROJECT_ADJUST = 5;

    const DJ_1 = 780; //冻结项目
    const DJ_2 = 1027; //冻结项目
    const DJ_3 = 184; //冻结项目

    use SoftDeletes, Notifiable,Filterable;
    protected $table = 'project';
    protected $fields_all;

    protected $fillable = [
        'pro_num','yl_num','uid','pid','units_id','units_dis','units_type','units_area','year_range','is_year',
        'year','pname','type','wf_id','yid','bid','zhuban','zhu_fuze','xieban','xie_fuze','proof',
        'money_stream','place_use','target','plan','lianxiren','tianbiaoren','tianbiao_date','pro_status',
        'ac_status','pro_type','pro_area','ac_status','is_new','amount','amount_now','again_status',
        'progress','is_party','m_score','advance_day','is_report','is_push','un_complete','county_pid',
        'relation_id','uptime','status_flow','fen_uid',
        'id','is_complete','is_adjust'
    ];

    //TODO 迁移数据时关闭
    public $timestamps = false;
    protected $datas = ['deleted_at'];


    public function supervise()
    {
        return $this->hasMany('App\Models\Supervise','pid','id');
    }

    //纳统获取对应的项目id
    public function reply()
    {
        return $this->hasMany('App\Models\Reply','reply_id','id')
            ->where('type',Reply::COMMENT_TYPE);
    }

    public function member()
    {
        return $this->belongsTo('App\Models\Member','user_id','id');
    }

    public function user() {
        return $this->belongsToMany(User::class, 'project_users');
    }

    public function users() {
        return $this->hasOne(User::class, 'id', 'uid');
    }

    public function type() {
        return $this->hasOne(Type::class, 'id', 'type');
    }

	//纳统获取对应的项目id
    public function natong()
    {
        return $this->hasOne('App\Models\Natong', 'pid', 'id')->orderBy('natongstatus_time','desc');
    }

    //project 关联wf_run
    public function wfrun()
    {
        return $this->hasOne('App\Work\Model\Run', 'from_id', 'id');
    }


    //项目和标签关联
    public function tag()
    {
        return $this->belongsToMany(Tag::class, 'project_tags');
    }


    public function comment()
    {
        return $this->hasMany('App\Models\Comment','pid','id');
    }

    //关联项目节点表 1对多
    public function plancustom()
    {
        return $this->hasMany('App\Models\ProjectPlanCustom','pid','id');
    }

    public function unit()
    {
        return $this->belongsTo('App\Models\Unit','units_id','id');
    }
    //关联工作流日志表 1对多
    public function runlog()
    {
        //排除系统 工作流这一步骤
        return $this->hasMany('App\Work\Model\RunLog','from_id','id')
            ->where([
                'from_table'=>'project',
                ['btn','!=','default']
            ]);
    }

    public function complete(){
        return $this->belongsTo('App\Models\Complete', 'id', 'pid');
    }

    public function adjust(){
        return $this->hasOne('App\Models\Adjust', 'pid', 'id');
    }

    //返回在建项目条件 $alias 是否别名
    public static function getjszProject($alias=0){
        switch ($alias)
        {
            case 1:
                //sta:overdue 175 行代码用到
               return [
                    ['g.p_status','=',4],
                    ['p.pro_status','!=',4],
                    ['p.pro_status','!=',5],
                    ['p.pro_status','!=',6],
                ];
              break;
            case 2:

                break;
            default:
                // 在建项目  排除 4：项目完结 5：调整项目 6：未完结项目
                return [
                    ['status_flow', '=', 2],
                    ['pro_status', '!=', 4],
                    ['pro_status', '!=', 5],
                    ['pro_status', '!=', 6]
                ];
        }
    }
    public function progressWrite()
    {
        return $this->hasOne('App\Models\Progress', 'pid', 'id');
    }
    public function natong_record()
    {
        return $this->hasMany('App\Models\NatongRecord', 'pid', 'id');
    }
    public function project()
    {
        return $this->hasOne('App\Models\Project', 'id', 'id');
    }
}
