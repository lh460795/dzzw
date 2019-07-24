<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/4
 * Time: 15:42
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use EloquentFilter\Filterable;

class UnitAvg extends model{

    use Filterable;

    protected $table = 'units_avg';
    protected $fields_all;
    protected $fillable = [
        'units_id', 'year', 'addtime', 'svg_score', 'rc_score',
        'id'
    ];
    //TODO 迁移数据时关闭
    public $timestamps = false;


}
