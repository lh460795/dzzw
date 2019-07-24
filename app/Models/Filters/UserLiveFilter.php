<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/13
 * Time: 10:22
 */
namespace App\Models\Filters;

use EloquentFilter\ModelFilter;

class UserLiveFilter extends ModelFilter{

    public $relations = [];

    public function is_live($is_live=1)
    {
        return $this->where('is_live',$is_live);
    }

    public function user_name($user_name){

        return $user_name&&$this->where('user_name', 'like',"%{$user_name}%")->orWhere('phone','like',"%{$user_name}%");

    }

    public function phone($phone){

        return $phone&&$this->where('phone','like',"%{$phone}%")->orWhere('user_name','like',"%{$phone}%");

    }
}