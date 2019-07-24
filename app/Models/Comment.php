<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;


class Comment extends Model implements PublishEvent
{
    use SoftDeletes,Filterable;

    protected $table = 'comment';
    protected $fillable = [
        'user_id','user_name','pid',
        'content','stars','created_at'
    ];

    //TODO 迁移数据时关闭
    public $timestamps = true;
    protected $datas = ['deleted_at'];

    public function reply()
    {
        return $this->hasMany('App\Models\Reply','reply_id','id')
            ->where(['type' => Reply::COMMENT_TYPE]);
    }


    public function project()
    {
        return $this->belongsTo('App\Models\Project','pid','id');
    }

    public static function getData()
    {
        //组合数据并排序
        $comments = Comment::get(['pid','created_at'])->toArray();
        $replys = Reply::where(['type' => Reply::COMMENT_TYPE])->get(['pid','created_at'])->toArray();
        $data = arraySort(array_merge($comments,$replys),'created_at');
        $pids = array_values(array_unique(array_column($data,'pid')));
        $page = request('page',1);
        $size = \request('per_page',5);
        $total = count($pids);
        $last_page = ceil($total/$size);
        $start = ($page-1)*$size;
        $end = $start+$size > $total ? $total : $start+$size;
        $tmp = [];
        for ($i=$start;$i<$end;$i++)
        {
            $data = Comment::with(['user' => function($query) {
                $query->with('corp:id,name');
            }])
                ->where(['pid' => $pids[$i]])
                ->orderBy('created_at','desc')
                ->get()
                ->toArray();
            $reply_count = Reply::where(['pid' => $pids[$i]])->count();
            $tmp[$i]['comment'] = $data[0];
            $tmp[$i]['total_count'] = count($data) + $reply_count;
            $tmp[$i]['pname'] = Project::find($pids[$i])->pname ?? 'mock';
        }

        $res = [
            'current_page' => $page,
            'data' => array_values($tmp),
            'last_page' => $last_page,
            'total' => $total
        ];

        return $res;
    }


    public function children()
    {
        return $this->hasMany(Reply::class,'pid','pid');
    }

    public function read_log()
    {
        return $this->hasMany(ReadLog::class,'relation_id','id')->where('type' ,ReadLog::COMMENT_TYPE);
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function($comment) {
            $user = Auth::user();
            $comment->user_id = $user['id'];
            $comment->user_name = $user['username'];
        });

        static::deleting(function($comment) {
            //同时删除子回复
            foreach ($comment->reply as $reply)
            {
                $reply->children()->delete();
            }
            $comment->reply()->delete();
        });

        static::created(function($comment) {
            //点评发布成功发送消息通知

            //判断角色
            $user = Auth::user();
            $roles = $user->role->toArray();

            //判断是否为市长或副市长
            $wechat_event = new WechatEvent();
            $role_names = array_column($roles,'display_name');
            if(in_array('市长',$role_names)) {
                $type = $wechat_event->events['mayor_comment'];
            }elseif (in_array('分管副市长',$role_names) || in_array('常务副市长',$role_names)){
                $type = $wechat_event->events['vice_mayor_comment'];
            }else{
                $type = $wechat_event->events['normal_comment'];
            }

            event(new \App\Events\PublishEvent($comment,$type));
        });
    }
}
