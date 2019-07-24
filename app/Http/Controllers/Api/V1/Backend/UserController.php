<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/5
 * Time: 17:30
 */
namespace App\Http\Controllers\Api\V1\Backend;


use App\Http\Requests\Api\UserCreateRequest;
use App\Http\Requests\Api\UserUpdateRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;
use App\models\User;
use App\Models\Role;
use App\Models\Corp;
use App\models\UserLive;

class UserController extends Controller{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function data(Request $request)
    {
        $model = User::query()->with(['corp','parent_corp']);
        //县级管理账号只能查看各自的账号
        //根据后台角色判断是否为市级用户
        !isCityUser() ? $model->where('parent_corp',Auth::user()->corp_id) : '';

        if ($request->get('name')){
            $model = $model->where('name','like','%'.$request->get('name').'%');
        }
        if ($request->get('phone')){
            $model = $model->where('phone','like','%'.$request->get('phone').'%');
        }
        $res = $model->orderBy('created_at','desc')->paginate($request->get('limit',30))->toArray();

        $data = [
            'count' => $res['total'],
            'data'  => $res['data']
        ];
        return $this->respond($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getCorps()
    {
        try{
            $corps = Corp::getCorps();
            if($corps){
                return $this->respond($corps);
            }else{
                return $this->failed('未获取有效组织架构信息',404);
            }
        }catch(\Exception $e){
            return $this->failed($e->getMessage(),500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserCreateRequest $request)
    {
        try{
            $data = $request->all();
            $data['password'] = bcrypt($data['password']);
            $data['uuid'] = \Faker\Provider\Uuid::uuid();
            $data['corp_id'] = $data['corp_three'] ?? ($data['corp_two'] ?? $data['corp_one']);
            $data['parent_corp'] = $data['corp_one'];
            $user=User::create($data);
            if ($user){
                $user_live=[
                    'username'=>$data['username'],
                    'phone'=>$data['phone'],
                    'district_id'=>$data['area_id'],
                    'units_id'=>$data['units_id'],
                    'timestamp'=>time(),
                    'user_id'=>$user->id
                ];
                UserLive::create($user_live);
                return $this->message('添加成功',200);
            }
            return $this->failed('添加新用户失败，请检查您的添加信息',404);
        }catch(\Exception $e){
            return $this->failed($e->getMessage(),500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getUserInfo($id)
    {
        try{
            $corps = Corp::getCorps();
            $user = User::findOrFail($id);
            switch ($user->corp->level)
            {
                case 1:
                    $user->corp_one = $user->corp_id;
                    break;
                case 2:
                    $user->corp_one = $user->corp->parent_id;
                    $user->corp_two = $user->corp_id;
                    break;
                case 3:
                    $corp = Corp::find($user->corp->parent_id);
                    $user->corp_three = $user->corp_id;
                    $user->corp_two = $user->corp->parent_id;
                    $user->corp_one = $corp->parent_id;
                    break;
            }
            $data=[
                'corps'=>$corps,
                'member'=>$user
            ];
            return $this->respond($data);
        }catch(\Exception $e){
            return $this->failed($e->getMessage(),500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserUpdateRequest $request, $id)
    {
        try{
            $user = User::findOrFail($id);
            $data = $request->except('password');
            $data['corp_id'] = $data['corp_three'] ?? ($data['corp_two'] ?? $data['corp_one']);
            $data['parent_corp'] = $data['corp_one'];
            if ($request->get('password')){
                $data['password'] = bcrypt($request->get('password'));
            }
            $record=$user->update($data);
            if ($record){
                return $this->respond($record);
            }
            return $this->failed('更新用户信息失败，请检查您的更新信息',500);
        }catch(\Exception $e){
            return $this->failed($e->getMessage(),500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        try{
            $ids = $request->get('ids');
            if (empty($ids)){
                return response()->json(['code'=>1,'msg'=>'请选择删除项']);
            }
            $records=User::destroy($ids);
            if ($records){
                return $this->respond($records);
            }
            return $this->failed('删除用户失败',500);

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }
    }

    /**
     * 分配角色
     */
    public function role(Request $request,$id)
    {
        $user = User::findOrFail($id);
        $roles = Role::where(['type' => 2])->get();
        $hasRoles = $user->roles();
        foreach ($roles as $role){
            $role->own = $user->hasRole($role) ? true : false;
        }
        $data=[
            'user'=>$user,
            'roles'=>$roles
        ];
        return $this->respond($data);
    }

    /**
     * 更新分配角色
     */
    public function assignRole(Request $request,$id)
    {
        $user = User::findOrFail($id);
        $roles = $request->get('roles',[]);
        if ($user->syncRoles($roles)){
            return $this->message('更新用户角色成功',200);
        }
        return $this->message('系统错误',500);
    }

    /**
     * 分配权限
     */
    public function permission(Request $request,$id)
    {
        try{
            $user = User::findOrFail($id);
            $permissions = $this->tree(Permission::FRONTEND_TYPE);
            foreach ($permissions as $key1 => $item1){
                $permissions[$key1]['own'] = $user->hasDirectPermission($item1['id']) ? 'checked' : false ;
                if (isset($item1['_child'])){
                    foreach ($item1['_child'] as $key2 => $item2){
                        $permissions[$key1]['_child'][$key2]['own'] = $user->hasDirectPermission($item2['id']) ? 'checked' : false ;
                        if (isset($item2['_child'])){
                            foreach ($item2['_child'] as $key3 => $item3){
                                $permissions[$key1]['_child'][$key2]['_child'][$key3]['own'] = $user->hasDirectPermission($item3['id']) ? 'checked' : false ;
                            }
                        }
                    }
                }
            }
            $data=[
                'user'=>$user,
                'permissions'=>$permissions
            ];
            return $this->respond($data);
        }catch(\Exception $e){
            return $this->failed($e->getMessage(),500);
        }
    }

    /**
     * 存储权限
     */
    public function assignPermission(Request $request,$id)
    {
        $user = User::findOrFail($id);
        $permissions = $request->get('permissions');

        if (empty($permissions)){
            $user->permissions()->detach();
            return $this->message('已更新用户直接权限',200);
        }
        $user->syncPermissions($permissions);
        return $this->message('已更新用户直接权限',200);
    }

}