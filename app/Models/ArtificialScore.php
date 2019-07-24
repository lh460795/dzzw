<?php

namespace App\Models;

use App\Traits\Frontend\ActionButtonTrait;
class ArtificialScore extends BaseModel
{
    //use SoftDeletes;
    use ActionButtonTrait;

    protected $table = 'wh_artificial_score';
    protected $fields_all;

    //TODO 迁移数据时关闭
    public $timestamps = false;

    public function month() {
        return $this->hasMany(MonthScore::class,'pid','pid')
                    ->orderBy('addtime', 'desc');
    }



}
