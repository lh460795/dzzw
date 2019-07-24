<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/20
 * Time: 19:48
 */
namespace App\Models\Filters;

use EloquentFilter\ModelFilter;

class ActivityLogFilter extends ModelFilter{

    public $relations = [];

    //针对下划线id
    protected $drop_id = false;
    //加了这个属性  过滤器才能生效
    protected $camel_cased_methods = false;

    public function start_date($start_date)
    {
        return $start_date&&$this->where('created_at','>=',$start_date.' 00:00:00');
    }

    public function end_date($end_date){

        return $end_date&&$this->where('created_at','<=',$end_date. ' 23:59:59');

    }

    public function type($type){

        return $type&&$this->where('type',$type);

    }

    public function type_id($type_id){

        return $type_id&&$this->where('type_id',$type_id);

    }


}