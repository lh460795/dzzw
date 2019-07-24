<?php

/**
 * 方法中检查是否有权限
 * */

namespace App\Http\Middleware\Api;

use Closure;
use Route;
use App\Models\Permission;
use Illuminate\Contracts\Auth\Guard;
use App\Traits\Api\ApiResponse;
class CheckPermission
{
    use ApiResponse;
    protected $auth;
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * 根据路由名称查询路由绑定的权限
     * 查询是否有权限
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $permission_info = Permission::where(['name' => \Request::route()->getName()])->first();
        if (empty($permission_info)) {
            return $next($request);
        }

        if (app('auth')->guard('api')->user()->can($permission_info['name'])) {
            return $this->failed('没有操作权限',403);
        }

        return $next($request);
    }

}
