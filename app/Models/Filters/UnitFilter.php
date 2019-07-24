<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/24
 * Time: 17:50
 */
namespace App\Models\Filters;

use EloquentFilter\ModelFilter;

class UnitFilter extends ModelFilter{

    public $relations = [];

    //针对下划线id
    protected $drop_id = false;
    //加了这个属性  过滤器才能生效
    protected $camel_cased_methods = false;

    public function name($name){

        return $name&&$this->where('name','like','%'.$name.'%');

    }

    public function id($id){

        return $id&&$this->where('id',$id);

    }

    public function parent_id($parent_id=0){

        return $this->where('parent_id',$parent_id);

    }
}