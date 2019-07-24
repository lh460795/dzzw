<?php

namespace App\Http\Controllers\Api\V1\Backend;

use App\Http\Requests\Api\UserCreateRequest;
use App\Http\Requests\Api\UserUpdateRequest;
use App\Models\Corp;
use App\Models\Permission;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Http\Controllers\Api\Controller;
class AdminUserController extends Controller
{
    //后台用户列表
    public function index(Request $request)
    {
        $per_page = empty($request->input('per_page'))?15:$request->input('per_page');
        $data = AdminUser::paginate($per_page);
        return $this->success($data);
    }


    //下拉列表数据
    public function unitsList()
    {
        $corps = getCorps();
        return $this->success($corps);
    }


    //新建用户
    public function store(UserCreateRequest $request)
    {
        $data = $request->all();
        $data['password'] = bcrypt($data['password']);
        $data['last_login_ip'] = request()->getClientIp();
        $data['last_login_time'] = time();
        $data['status'] = 0;
        try {
            AdminUser::create($data);
            return $this->message('新建成功');
        } catch (\Exception $e) {
            return $this->failed('新建失败');
        }
    }

    //获取详情
    public function show(Request $request) {
        $id = $request->input('id');

        if (empty($id)) {
            return $this->failed('id 不能为空');
        }

        try {
            $data = AdminUser::find($id);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->failed('操作异常');
        }

    }

    //修改用户
    public function update(UserUpdateRequest $request)
    {

        try {
            $id = $request->input('id');
            $user = AdminUser::findOrFail($id);
            $data = $request->except('password');

            if ($request->input('password')){
                $data['password'] = bcrypt($request->input('password'));
            }

            $user->update($data);
            return $this->message('修改成功');
        } catch (\Exception $e) {
            return $this->failed('修改失败');
        }
    }

    //删除用户
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');
        if (empty($ids)){
            return $this->failed('id 不能为空');
        }

        try {
            AdminUser::destroy($ids);
            return $this->message('删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    /**
     * 分配角色
     */
    public function role(Request $request)
    {
        $id = $request->input('id');
        if (empty($id)) {
            return $this->failed('id 不能为空');
        }

        $user =  AdminUser::findOrFail($id);
        $roles = Role::get();
        foreach ($roles as $role){
            $role->own = $user->hasRole($role) ? true : false;
        }

        $data = [
            'role' => $roles,
            'user' => $user
        ];

        return $this->success($data);
    }

    /**
     * 更新分配角色
     */
    public function assignRole(Request $request)
    {
        try {
            $user = AdminUser::findOrFail(\Auth::guard('admins')->id());
            $roles = $request->input('roles',[]);

            $user->syncRoles($roles);
            return $this->message('分配成功');
        } catch (\Exception $e) {
            return $this->failed('分配失败');
        }

    }

    /**
     * 分配权限
     */
    public function permission(Request $request)
    {
        $user = AdminUser::findOrFail(\Auth::guard('admins')->id());
        $permissions = $this->tree(Permission::ADMIN_TYPE);

        foreach ($permissions as $key1 => $item1){
            $permissions[$key1]['own'] = $user->hasDirectPermission($item1['name']) ? 'checked' : false ;
            if (isset($item1['_child'])){
                foreach ($item1['_child'] as $key2 => $item2){
                    $permissions[$key1]['_child'][$key2]['own'] = $user->hasDirectPermission($item2['name']) ? 'checked' : false ;
                    if (isset($item2['_child'])){
                        foreach ($item2['_child'] as $key3 => $item3){
                            $permissions[$key1]['_child'][$key2]['_child'][$key3]['own'] = $user->hasDirectPermission($item3['name']) ? 'checked' : false ;
                        }
                    }
                }
            }
        }

        $data = [
            'user' => $user,
            'permission' => $permissions
        ];

        return $this->success($data);
    }

    /**
     * 存储权限
     */
    public function assignPermission(Request $request)
    {

        try {
            $user = AdminUser::findOrFail(\Auth::guard('admins')->id());
            $permissions = $request->input('permissions');

            if (empty($permissions)){
                $user->permissions()->detach();
                return $this->failed('已经更新过权限', 400);
            }

            $user->syncPermissions($permissions);
            return $this->message('分配权限成功');
        } catch (\Exception $e) {
            return $this->failed('分配权限失败', 400);
        }

    }

    //权限菜单
    public function menu()
    {
        $menus = \App\Models\Permission::with('childs')
            ->where(['parent_id'=>0,'type' => 1])
            ->orderBy('sort','desc')
            ->get();

        $data = [
            'menus' => $menus,
        ];

        return $this->success($data);
    }

    //角色的权限列表
    public function permissions() {
        $user = \Auth::guard('admins')->user();
        $permissions = $user->getAllPermissions();
        $permissions = $permissions->where('type', 1);
        $permissions = $permissions->toArray();
        $temp = [];

        foreach ($permissions as $k=>$v) {
            unset($v['type']);
            unset($v['name']);
            unset($v['guard_name']);
            unset($v['sort']);
            unset($v['created_at']);
            unset($v['updated_at']);
            unset($v['pivot']);
            $permissions[$k]['itemIcon'] = $v['icon_id'];
            unset($v['icon_id']);
            $temp[$k] = $v;
            $temp[$k]['itemIcon'] = $permissions[$k]['itemIcon'];
        }


        $tree = $this->tree(Permission::ADMIN_TYPE, $temp);
        $tree = $this->compose($tree);
        return $this->success($tree);
    }

}
