<?php
namespace App\Models;

class Role extends \Spatie\Permission\Models\Role
{
    const ADMIN_TYPE = 1;
    const FRONTENT_TYPE = 2;

    const projectOperator = 21;
    const Operator  =  22;
    const Secretary =  23;
    const wuhua =  25;
    const vicemayor =  24;
    const routineMayor =  26;
    const mayor =  27;
    const unitsLeader = 29;
    const seLeader = 31;

    public function corp()
    {
        return $this->belongsTo('App\Models\Corp','corp_id','id');
    }

    public function pending() {
        return $this->belongsToMany(Pending::class, 'role_pendings');
    }

    public function todo() {
        return $this->belongsToMany(Todo::class, 'role_todos');
    }
}
