<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$api = app('Dingo\Api\Routing\Router');
$api->version('V1', [
    'namespace' => 'App\Http\Controllers\Api\V1\Backend',
    'middleware' => [
        'cors',
    ],
], function($api) {

    $api->group([
        'middleware' => 'api.throttle',
        'limit' => config('api.rate_limits.sign.limit'),
        'expires' => config('api.rate_limits.sign.expires'),
    ], function($api) {
        // 登录
        $api->post('authorizations', 'AuthorizationsController@store')
            ->name('api.authorizations.store');

        // 刷新token
        $api->put('authorizations/current', 'AuthorizationsController@update')
            ->name('api.authorizations.update');

        // 删除token
        $api->delete('authorizations/current', 'AuthorizationsController@destroy')
            ->name('api.authorizations.destroy');
    });

    //点评管理
    $api->get('comments', 'CommentController@index')->name('admin.comments');
    $api->get('comments/reply', 'CommentController@reply')->name('admin.comments.reply');
    $api->get('comments/{id}/edit', 'CommentController@edit')->name('admin.comments.edit');
    $api->put('comments/{id}/update', 'CommentController@update')->name('admin.comments.update');
    $api->delete('comments/destroy', 'CommentController@destroy')->name('admin.comments.destroy');

    //述评管理
    $api->get('reviews', 'ReviewController@index')->name('admin.reviews');
    $api->get('reviews/reply', 'ReviewController@reply')->name('admin.reviews.reply');
    $api->get('reviews/{id}/edit', 'ReviewController@edit')->name('admin.reviews.edit');
    $api->put('reviews/{id}/update', 'ReviewController@update')->name('admin.reviews.update');
    $api->delete('reviews/destroy', 'ReviewController@destroy')->name('admin.reviews.destroy');

    $api->get('test', 'OptionController@index')
        ->name('api.option.test1');

    $api->group([
        'middleware' => 'auth:admins'
    ],function($api){
        //账号管理
            $api->get('user/data', 'UserController@data');
            $api->get('user', 'UserController@index');
            //添加
            $api->get('user/create', 'UserController@create');
            $api->post('user/store', 'UserController@store')->name('admin.user.store')->middleware('permission:system.user.create');
            //编辑
            $api->get('user/{id}/edit', 'UserController@edit');
            $api->put('user/{id}/update', 'UserController@update')->name('admin.user.update')->middleware('permission:system.user.edit');
            //删除
            $api->delete('user/destroy', 'UserController@destroy')->name('admin.user.destroy')->middleware('permission:system.user.destroy');
            //分配权限
            $api->get('user/{id}/permission','UserController@permission');
            $api->put('user/{id}/assignPermission','UserController@assignPermission');
            //分配角色
            $api->get('user/{id}/role','UserController@role')->name('admin.user.role')->middleware('permission:system.user.role');
            $api->put('user/{id}/assignRole','UserController@assignRole')->name('admin.user.assignRole')->middleware('permission:system.user.role');
            //获取组织架构信息
            $api->get('user/getCorps','UserController@getCorps');
    });

    $api->group([
        'middleware'=> 'auth:admins'
    ],function($api){
            //组织架构管理
            $api->get('corp/data', 'CorpController@data')->name('admin.corp.data');
            //添加
            $api->get('corp/info', 'CorpController@info')->name('admin.corp.info');
            $api->post('corp/store', 'CorpController@store')->name('admin.corp.store');
            //编辑
            $api->get('corp/{id}/getUserCorp', 'CorpController@getUserCorp')->name('admin.corp.getUserCorp')->middleware('permission:admin.corp.getUserCorp');
            $api->put('corp/{id}/update', 'CorpController@update')->name('admin.corp.update')->middleware('permission:admin.corp.edit');
            //删除
            $api->delete('corp/destroy', 'CorpController@destroy')->name('admin.corp.destroy')->middleware('permission:admin.corp.destroy');
    });

    $api->group([
        //'prefix' => 'frontend',
        'middleware' => 'auth:admins'
    ], function ($api) {
        //用户管理
        $api->get('admin/index', 'AdminUserController@index')
            ->name('admin.index');

        $api->get('admin/menu', 'AdminUserController@menu')
            ->name('admin.menu');

        $api->get('admin/unitsList', 'AdminUserController@unitsList')
            ->name('admin.unitsList');

        $api->post('admin/create', 'AdminUserController@store')
            ->name('admin.create');

        $api->get('admin/show', 'AdminUserController@show')
            ->name('admin.show');

        $api->post('admin/update', 'AdminUserController@update')
            ->name('admin.update');

        $api->post('admin/delete', 'AdminUserController@destroy')
            ->name('admin.delete');

        $api->get('admin/role', 'AdminUserController@role')
            ->name('admin.role');

        $api->post('admin/assignrole', 'AdminUserController@assignRole')
            ->name('admin.assignrole');

        $api->get('admin/permission', 'AdminUserController@permission')
            ->name('admin.permission');

        $api->post('admin/assignpermits', 'AdminUserController@permission')
            ->name('admin.assignpermits');

        //角色管理
        $api->get('role/index', 'RoleController@index')
            ->name('admin.role.index');

        $api->post('role/create', 'RoleController@store')
            ->name('admin.role.create');

        $api->get('role/show', 'RoleController@show')
            ->name('admin.role.show');

        $api->post('role/update', 'RoleController@update')
            ->name('admin.role.update');

        $api->post('role/delete', 'RoleController@destroy')
            ->name('admin.role.delete');

        $api->get('role/permits', 'RoleController@permission')
            ->name('admin.role.permits');

        $api->post('role/assignpermits', 'RoleController@assignPermission')
            ->name('admin.role.assignpermits');

        //权限管理
        $api->get('permission/index', 'PermissionController@index')
            ->name('admin.permission.index');

        $api->get('permission/sublist', 'PermissionController@sublist')
            ->name('admin.permission.index');

        $api->get('permission/subindex', 'PermissionController@subindex')
            ->name('admin.permission.subindex');

        $api->post('permission/create', 'PermissionController@store')
            ->name('admin.permission.create');

        $api->get('permission/show', 'PermissionController@show')
            ->name('admin.permission.show');

        $api->post('permission/update', 'PermissionController@update')
            ->name('admin.permission.update');

        $api->post('permission/delete', 'PermissionController@destroy')
            ->name('admin.permission.delete');

        //后台权限列表
        $api->get('user/permits', 'AdminUserController@permissions')
            ->name('admin.user.permits');

    });

    // 附件管理
    $api->group([
        // 'middleware' => 'auth:admins',
        'prefix'     => 'backend',
    ], function($api){
        //附件列表
        $api->get('upload', 'UploadController@index')->name('admin.upload');
        //添加
        $api->get('upload/create', 'UploadController@create')->name('admin.upload.create');
        $api->post('upload/store', 'UploadController@store')->name('admin.upload.store');
        //编辑
        $api->get('upload/{id}/edit', 'UploadController@edit')->name('admin.upload.edit');
        $api->post('upload/{id}/update', 'UploadController@update')->name('admin.upload.update');
        //删除
        $api->delete('upload/destroy', 'UploadController@destroy')->name('admin.upload.destroy');
    });

    // 资讯管理
    $api->group([
        // 'middleware' => 'auth:admins',
        'prefix'     => 'backend',
    ], function($api){
        // 分类管理
        $api->group(['prefix' => 'category'], function($api){
            $api->get('index', 'CategoryController@index');    // 分类页面展示 /api/backend/category/index 

            $api->get('show', 'CategoryController@show');      // 添加分类展示  /api/backend/category/show  
            $api->post('add', 'CategoryController@store');     // 添加分类   /api/backend/category/add

            $api->match(['get', 'post'], '{id}/edit', 'CategoryController@edit');  //修改分类   /api/backend/category/{id}/edit

            $api->delete('{id}/del', 'CategoryController@del');  // 软删除  /api/backend/category/{id}/del
        }); 

        // 标签管理
        $api->group(['prefix' => 'label'], function($api){
            $api->get('index', 'LabelController@index');      // 标签页面展示  /api/backend/label/index

            $api->post('add', 'LabelController@add');         // 标签添加     /api/backend/label/add

            $api->match(['get', 'post'], '{id}/edit', 'LabelController@edit');       // 修改操作     /api/backend/label/edit

            $api->delete('{id}/del', 'LabelController@del'); // 删除操作  /api/backend/label/del
        });

        // 文章管理
        $api->group(['prefix' => 'article'], function($api){
            $api->get('list', 'ArticleController@list');       // 文章列表页面   /api/backend/article/list

            $api->get('{id}/show', 'ArticleController@show');  // 文章展示页面   /api/backend/article/show

            $api->get('create', 'ArticleController@create');  // 文章添加页面    /api/backend/article/create
            $api->post('store', 'ArticleController@store');   // 文章添加操作    /api/backend/article/store

            $api->get('{id}/edit', 'ArticleController@edit');   // 文章修改页面        /api/backend/article/edit
            $api->post('{id}/update', 'ArticleController@update'); // 文章更新操作     /api/backend/article/update

            $api->delete('{id}/del', 'ArticleController@del');  // 软删除        /api/backend/article/del

            $api->get('{id}/increase', 'ArticleController@increase'); // 访问量
        });
    });

});
