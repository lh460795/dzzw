<?php

namespace App\Traits;

trait District {
    public function register()
    {
        if($this->app->runningInConsole()){
            return;
        }

        $distirct = request()->input('district')??'孝感';
        $db_id = \DB::connection('system')->table('district')->where('district', $distirct)->value('db_id');
        $db_name = \DB::connection('system')->table('db')->where('id', $db_id)->value('db');
        config(['database.connections.tenant.database' => $db_name]);//$db_name
        \DB::purge('tenant');
        \DB::reconnect('tenant');
    }
}