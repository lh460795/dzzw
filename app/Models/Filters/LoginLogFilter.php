<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/26
 * Time: 14:27
 */
namespace App\Models\Filters;

use EloquentFilter\ModelFilter;


class LoginLogFilter extends ModelFilter{

    public $relations = [];

    protected $drop_id = false;

    protected $camel_cased_methods = false;

    public function start_date($start_date){

        return $start_date&&$this->where('login_time','>=',strtotime($start_date.' 00:00:00'));

    }

    public function end_date($end_date){

        return $end_date&&$this->where('login_time','<=',strtotime($end_date.' 23:59:59'));

    }

    public function area_id($area_id){

        return $area_id&&$this->where('area_id',$area_id);

    }

    public function units_id($units_id){

        return $units_id&&$this->where('units_id',$units_id);

    }

    public function units($units){

        return $units&&$this->where('units','like','%'.$units.'%');

    }

    public function client($client)
    {
        switch ($client) {
            case 1:
                return $this->where('platform', 'pc');
                break;
            case 2:
                return $this->where('platform', '微信');
                break;
            case 3:
                return $this->where('platform', '小程序');
                break;
        }
    }

    public function user_name($user_name){

        return $user_name&&$this->where('user_name', 'like',"%{$user_name}%")->orWhere('phone','like',"%{$user_name}%");

    }

    public function phone($phone){

        return $phone&&$this->where('phone','like',"%{$phone}%")->orWhere('user_name','like',"%{$phone}%");

    }
}