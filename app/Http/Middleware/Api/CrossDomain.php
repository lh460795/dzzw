<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/21
 * Time: 15:18
 */
namespace App\http\Middleware\Api;

use Closure;

class CrossDomain
{
    public function handle($request,Closure $next){

        $response=$next($request);

        $response->header('Content-type','application/json')
            ->header('Access-Control-Allow-Origin','*')
            ->header('Access-Control-Allow-Headers','Origin,Content-Type,Cookie,Accept,multipart/form-data,application/json,X-Auth-Token')
            ->header('Access-Control-Allow-Methods','GET,POST,PATCH,PUT,OPTION')
            ->header('Access-Control-Allow-Credentials','false');
//            ->header('Content-Disposition: attachment; filename='."ActivityLog".date('YmdHis'))
//            ->header('Content-Type:application/force-download')
//            ->header('Content-Transfer-Encoding:binary');
        return $response;
    }
}