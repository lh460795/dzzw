<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use EloquentFilter\Filterable;

class Natong extends BaseModel
{
    use Filterable;
    protected $table = 'natong';

    /**
     * 获取到所有的纳统相关内容
     * 0未纳；1应纳；2已纳；3正在申报；4等待申报；5资料不全；6后期纳统；7其他；
     * 8等待核实，9系统判定未纳统，10系统判定应统未统，11系统判定疑似未纳统，
     * 12、在系统判定的3种情况下取消标记，13、系统判定未纳统人工标记未纳统，14、系统判定疑似未纳统人工判定未纳统
     */

    public function project()
    {
        return $this->belongsTo('App\Models\Project', 'pid', 'id');
    }

    public function runs()
    {
        return $this->hasOne('App\Work\Model\run', 'from_id', 'pid');
    }

    public function natongRecord()
    {
        return $this->hasMany('App\Models\NatongRecord', 'pid', 'pid');
    }

}