<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/30
 * Time: 17:36
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use EloquentFilter\Filterable;

class LoginLog extends Model{

    protected $connection;
    protected $table = 'login_log';

    use Filterable;

    public function user_lives(){

        return $this->hasOne('App\Models\UserLive','user_id','user_id')->where('is_live',1);

    }
}