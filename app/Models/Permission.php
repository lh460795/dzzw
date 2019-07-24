<?php
namespace App\Models;

class Permission extends \Spatie\Permission\Models\Permission
{
    const ADMIN_TYPE = 1;
    const FRONTEND_TYPE = 2;

    protected $table='permissions';

    //菜单图标
    public function icon()
    {
        return $this->belongsTo('App\Models\Icon','icon_id','id');
    }

    //子权限
    public function childs()
    {
        return $this->hasMany('App\Models\Permission','parent_id','id');
    }

    public function role_has_permission()
    {
        return $this->hasMany('App\Models\Permission','permission_id','id');
    }

}
