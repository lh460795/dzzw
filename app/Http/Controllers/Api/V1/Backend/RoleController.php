<?php

namespace App\Http\Controllers\Api\V1\Backend;

use App\Http\Requests\Api\RoleCreateRequest;
use App\Http\Requests\Api\RoleUpdateRequest;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Http\Controllers\Api\Controller;
class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = empty($request->input('per_page'))?15:$request->input('per_page');
        $data = Role::paginate($per_page);
        return $this->success($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $corps = getCorps();
        return $this->success($corps);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RoleCreateRequest $request)
    {

        try {
            $data = $request->only(['name','display_name','type','corp_id']);
            $data['type'] = 1;
            Role::create($data);
            return $this->message('新建成功');
        }catch (\Exception $e) {
            return $this->failed('创建失败', 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $id = $request->input('id');

        if (empty($id)) {
            return $this->failed('id 不能为空');
        }


        try {
            $data = Role::find($id);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->failed('操作异常');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $corps = Helper::getCorps();
        return view('admin.role.edit',compact('role','corps'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(RoleUpdateRequest $request)
    {
        try {
            $id = $request->input('id');
            $role = Role::findOrFail($id);
            $data = $request->only(['name','display_name','type','corp_id']);
            $role->update($data);
            return $this->message('修改成功');
        }catch (\Exception $e) {
            return $this->failed('修改失败',400);
        }
    }

    // 删除角色
    public function destroy(Request $request)
    {
        $ids = $request->input('ids');
        if (empty($ids)){
            return $this->failed('id 不能为空');
        }

        try {
            Role::destroy($ids);
            return $this->message('删除成功');
        } catch (\Exception $e) {
            return $this->failed('删除失败');
        }
    }

    /**
     * 分配权限
     */
    public function permission(Request $request)
    {
        $id = $request->input('id');
        if (empty($id)) {
            return $this->failed('id不能为空',400);
        }

        $role = Role::findOrFail($id);
        $permissions = $this->tree($request->type ?? 1);

        foreach ($permissions as $key1 => $item1){
            $permissions[$key1]['own'] = $role->hasPermissionTo($item1['name']) ? 'checked' : false ;
            if (isset($item1['_child'])){
                foreach ($item1['_child'] as $key2 => $item2){
                    $permissions[$key1]['_child'][$key2]['own'] = $role->hasPermissionTo($item2['name']) ? 'checked' : false ;
                    if (isset($item2['_child'])){
                        foreach ($item2['_child'] as $key3 => $item3){
                            $permissions[$key1]['_child'][$key2]['_child'][$key3]['own'] = $role->hasPermissionTo($item3['name']) ? 'checked' : false ;
                        }
                    }
                }
            }
        }

        $data = [
            'role' => $role,
            'permission' => $permissions
        ];

        return $this->success($data);
    }

    /**
     * 存储权限
     */
    public function assignPermission(Request $request)
    {
        $id = $request->input('id');
        if (empty($id)) {
            return $this->failed('id不能为空',400);
        }

        try {
            $role = Role::findOrFail($id);
            $permissions = $request->input('permissions');

            if (empty($permissions)){
                $role->permissions()->detach();
                return $this->failed('已经更新过权限', 400);
            }

            $role->syncPermissions($permissions);
            return $this->message('分配权限成功');
        } catch (\Exception $e) {
            return $this->failed('分配权限失败', 400);
        }
    }



}
