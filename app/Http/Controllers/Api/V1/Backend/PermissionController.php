<?php

namespace App\Http\Controllers\Api\V1\Backend;

use App\Http\Requests\Api\PermissionCreateRequest;
use App\Http\Requests\Api\PermissionUpdateRequest;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use App\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $per_page = empty($request->input('per_page'))?15:$request->input('per_page');
        $data = Permission::where('type', Permission::ADMIN_TYPE)
                ->where('parent_id', 0)->paginate($per_page);

        return $this->success($data);

    }

    //权限下拉列表
    public function sublist() {
        $permissions = $this->tree(request('type',1));
        return $this->success($permissions);
    }

    //子权限列表
    public function subindex(Request $request) {
        $id = $request->input('id');
        if (empty($id)) {
            $this->failed('id 不能为空');
        }

        $per_page = empty($request->input('per_page'))?15:$request->input('per_page');
        $data = Permission::where('type', Permission::ADMIN_TYPE)
            ->where('parent_id', $id)->paginate($per_page);

        return $this->success($data);

    }


    //新增权限
    public function store(PermissionCreateRequest $request)
    {
        try {
            $data = $request->all();
            $data['type'] = Permission::ADMIN_TYPE;
            Permission::create($data);
            return $this->message('新建成功');
        }catch (\Exception $e) {
            return $this->failed('新建失败');
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
            $data = Permission::find($id);
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
        $type = request('type',Permission::ADMIN_TYPE);
        $permission = Permission::findOrFail($id);
        $permissions = $this->tree($type);
        return view('admin.permission.edit',compact('permission','permissions'));
    }


    //更新权限
    public function update(PermissionUpdateRequest $request)
    {

        try {
            $id = $request->input('id');
            $permission = Permission::findOrFail($id);
            $data = $request->all();
            $permission->update($data);
            return $this->message('修改成功');
        } catch (\Exception $e) {
            return $this->failed('修改失败');
        }
    }


    //删除权限
    public function destroy(Request $request)
    {
        $ids = $request->get('ids');
        if (empty($ids)){
            return $this->failed('ids不能为空');
        }

        try {
            $permission = Permission::whereIn('id', $ids)->get();
            if (collect($permission)->isEmpty()){
                return $this->failed('权限不存在');
            }

            //如果有子权限，则禁止删除
            $record = Permission::whereIn('parent_id',$ids)->first();
            if (collect($record)->isNotEmpty()){
                return $this->failed('存在子权限');
            }

            Permission::destroy($ids);

            return $this->message('删除成功');
        }catch (\Exception $e) {
            dd($e->getMessage());
            return $this->failed('删除失败');
        }
    }
}
