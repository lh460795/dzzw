<?php

namespace App\Models;

class MonthScoreHistory extends BaseModel
{
    //use SoftDeletes;

    protected $table = 'wh_month_score_history';
    protected $fields_all;
    protected $fillable = [
        'score_id', 'pid', 'addtime', 'month', 'year', 'data'
    ];
    //TODO 迁移数据时关闭
    //public $timestamps = false;



}
