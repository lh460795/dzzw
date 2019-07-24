<?php

namespace App\Models;

use Illuminate\Support\Facades\Auth;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Review extends Model implements PublishEvent
{
    use SoftDeletes,Filterable;

    protected $table = 'review';
    protected $fillable = [
        'user_id','user_name','title',
        'content','fresh_time','created_at','is_top'
    ];
    protected $datas = ['deleted_at'];
    //TODO
    public $timestamps = true;
    protected $appends = ['total_count'];


    public function reply()
    {
        return $this->hasMany('App\Models\Reply','reply_id','id')
            ->where('type',Reply::REVIEW_TYPE);
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User','user_id','id');
    }

    public function read_log()
    {
        return $this->hasMany(ReadLog::class,'relation_id','id')->where('type' ,ReadLog::REVIEW_TYPE);
    }

    /*
     * 统计回复数(包含子回复)
     */
    public function getTotalCountAttribute($value)
    {
        $reply_count = count($this->reply);
        $children_count = 0;

        foreach ($this->reply as $reply)
        {
            $children_count += count($reply->children);
        }
        return $reply_count + $children_count;
    }



    protected static function boot()
    {
        parent::boot();

        static::creating(function($review) {
            $user = Auth::user();
            $review->fresh_time = date('Y-m-d H:i:s');
            $review->user_id = $user['id'];
            $review->user_name = $user['username'];
        });

        static::deleting(function($review) {
            //同时删除子回复
            foreach ($review->reply as $reply)
            {
                $reply->children()->delete();
            }
            $review->reply()->delete();
        });

        static::created(function($review) {
            $wechat_event = new WechatEvent();
            event(new \App\Events\PublishEvent($review,$wechat_event->events['review_publish']));
        });
    }
}
