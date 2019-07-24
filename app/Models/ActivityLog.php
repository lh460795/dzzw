<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/31
 * Time: 18:04
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use EloquentFilter\Filterable;

class ActivityLog extends Model{

    use Filterable;

    protected $table = 'activity_log';

    public function user(){

        return $this->hasOne('App\Models\User','id','causer_id');

    }

}