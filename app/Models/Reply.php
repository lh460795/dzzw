<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;



class Reply extends Model implements PublishEvent
{
    use SoftDeletes,Filterable;

    const COMMENT_TYPE = 1;
    const REVIEW_TYPE = 2;

    protected $table = 'reply';
    protected $fillable = [
        'user_id','user_name','review_id',
        'content','parent_id','type','created_at',
        'reply_id','pid','to_id'
    ];

    protected $datas = ['deleted_at'];

    //TODO
    public $timestamps = true;

    public function children()
    {
        return $this->hasMany('App\Models\Reply','parent_id','id');
    }

    public function project()
    {
        return $this->belongsTo('App\Models\Project','pid','id');
    }

    public function toReply()
    {
        return $this->belongsTo('App\Models\Reply','to_id','id');
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }

    public function parent()
    {
        return $this->belongsTo('App\Models\Reply','parent_id','id');
    }


    protected static function boot()
    {
        parent::boot();

        static::deleting(function($reply) {
            $reply->children()->delete();
        });

        static::creating(function($reply) {
            $user = Auth::user();
            $reply->user_id = $user['id'];
            $reply->user_name = $user['username'];
        });


        static::created(function($reply) {
            //若为述评回复则更新时间
            if ($reply->type == 2) {
                $tmp['fresh_time'] = date('Y-m-d H:i:s');
                $id = $reply->reply_id;
                if($id == 0) {
                    $id = self::find($reply->parent_id)->reply_id;
                }
                Review::where(['id' => $id])->update($tmp);
            }

            //发布成功发送消息通知
            $wechat_event = new WechatEvent();
            if($reply->type == 1) {
                //判断角色
                $user = Auth::user();
                $roles = $user->role->toArray();
                //判断是否为市长
                $role_names = array_column($roles,'display_name');
                if(in_array('市长',$role_names)) {
                    $type = $wechat_event->events['mayor_reply_comment'];
                }else{
                    $type = $wechat_event->events['normal_reply_comment'];
                }
            }

            event(new \App\Events\PublishEvent($reply,$type ?? $wechat_event->events['review_reply']));
        });
    }
}
