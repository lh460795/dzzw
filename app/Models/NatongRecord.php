<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NatongRecord extends Model
{
    protected $table = 'natong_jilu';

    //批量赋值字段
    protected $fillable = [
        'pid', 'natong_status', 'natong_reason', 'natong_number', 'uid', 'edit_time',
    ];

    public function natong()
    {
        return $this->belongsTo('App\Models\Natong', 'pid', 'pid');
    }

    public function project()
    {
        return $this->belongsTo('App\Models\project', 'pid', 'id');
    }
}