<?php

namespace App\Models;

use App\Models\User;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
class ProjectDraft extends BaseModel
{
    use SoftDeletes, Notifiable,Filterable;
    protected $table = 'project_draft';
    protected $fields_all;

    protected $fillable = [
        'pro_num','yl_num','uid','pid', 'year','pname','type','wf_id'
    ];

    //TODO 迁移数据时关闭
    public $timestamps = true;
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
	
	//纳统获取对应的项目id
    public function natong()
    {
        return $this->hasOne('App\Models\Natong', 'pid', 'id');
    }

    //project 关联wf_run
    public function wfrun()
    {
        return $this->hasOne('App\Work\Model\run', 'from_id', 'id');
    }

    //项目和标签关联
    public function tag()
    {
        return $this->belongsToMany(Tag::class, 'project_users');
    }
}
