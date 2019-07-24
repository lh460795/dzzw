<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/24
 * Time: 17:50
 */
namespace App\Models\Filters;

use EloquentFilter\ModelFilter;

class UserFilter extends ModelFilter{

    public $relations = [];

    //针对下划线id
    protected $drop_id = false;
    //加了这个属性  过滤器才能生效
    protected $camel_cased_methods = false;

    public function username($username){

        return $username&&$this->where('username','like','%'.$username.'%');

    }

    public function units_id($units_id){

        return $units_id&&$this->where('units_id',$units_id);

    }

    public function corp_id($corp_id){

        return $corp_id&&$this->where('corp_id',$corp_id);

    }
}