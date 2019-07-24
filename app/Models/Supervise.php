<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Supervise extends Model implements PublishEvent
{
    const CREATED_AT = 'addtime';
    const UPDATED_AT = null;
    const STATUS_CHECKED = 0;
    const STATUS_REPLYED = 1;
    const STATUS_CONFIRMED = 2;

    protected $table = 'wh_duban_record';
    public $timestamps = true;

    protected $casts = [
        'addtime' => 'datetime',
        'limit_time' => 'datetime',
        'read_time' => 'datetime',
    ];

    protected $fillable = [
        'uid','touid','content',
        'addtime','pid','limit_time',
        'status','is_read','read_time'
    ];

    public function project()
    {
        return $this->belongsTo('App\Models\Project','pid','id');
    }

    public function getLimitTimeAttribute($value){
        return $value ? $value : '';
    }

    public function getReadTimeAttribute($value){
        return $value ? $value : '';
    }

    public function upload()
    {
        return $this->hasMany(Upload::class,'relation_id','id')->where(['file_type' =>7]);
    }

    public function reply()
    {
        return $this->hasMany('App\Models\SuperviseReply','duban_id','id');
    }

    protected static function boot()
    {
        parent::boot();


        static::creating(function($model) {
            $model->uid = Auth::id();
        });

    }
}
