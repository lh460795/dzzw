<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/30
 * Time: 16:37
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model{

    protected $table = 'organization';

    public function User(){

        return $this->hasMany('App\models\User','org_id','id')->orderBy('created_at','asc');

    }

}