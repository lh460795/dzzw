<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SuperviseReply extends Model
{
    const CREATED_AT = 'addtime';
    const UPDATED_AT = null;

    protected $table = 'wh_duban_reply';
    public $timestamps = true;

    protected $casts = [
        'addtime' => 'datetime',
    ];

    protected $fillable = [
        'uid','touid','content',
        'addtime','pid','duban_id','is_finish'
    ];

    public function project()
    {
        return $this->belongsTo('App\Models\Project','pid','id');
    }


    public function upload()
    {
        return $this->hasMany('App\Models\Upload','relation_id','id')->where(['type' =>8]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function($model) {
            $model->uid = Auth::id();
        });

        //督办回复更新督办状态
        static::created(function($model) {
            Supervise::where(['id' =>$model->duban_id])->update(['status' => Supervise::STATUS_REPLYED]);
        });

    }

}
