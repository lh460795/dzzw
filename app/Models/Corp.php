<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Corp extends Model
{
    use SoftDeletes;
    protected $table = 'corp';
    protected $datas = ['deleted_at'];

    //子结构
    public function childs()
    {
        return $this->hasMany('App\Models\Corp','parent_id','id');
    }

    public function parent()
    {
        return $this->belongsTo('App\Models\Corp','parent_id','id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name','parent_id','level',
        'weight','alias_name',
    ];

    /*
     *根据用户返回组织架构
     */
    public static function getCorps()
    {
        $user_id = Auth::id();
        $user = User::with('role')->find($user_id)->toArray();
        $role_ids = array_column($user['role'],'id');
        //根据后台角色判断是否为市级用户
        if(in_array(config('role.city_role_id'),$role_ids)) {
            $corps = Corp::all()->toArray();
            return $corps;
        }else{
            $corps = Corp::with(['childs'=>function ($query){
                $query->with('childs');
            }])->where(['id' => $user['corp_id']])->get()->toArray();
            $data = self::getData($corps);
            return $data;
        }
    }

    public static function getData($corps)
    {
        static $tmp = [];
        foreach ($corps as $corp)
        {
            array_push($tmp,$corp);
            if(!empty($corp['childs'])) {
                self::getData($corp['childs']);
            }
        }
        return $tmp;
    }
}
