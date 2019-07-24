<?php

namespace App\Http\Controllers\Api\V1\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\CategoryRequest;
use App\Models\Category;


/**
 * 分类管理模块
 **/
class CategoryController extends Controller {

	/**
	 * 分类展示页面接口
	 * @param $request
	 * @return json 
	 **/
	public function index(){
		//递归所有分类
		$category = new Category();
		$res= $category->getAllData();
		return $this->success($res);
	}

	/**
	 * 添加分类页面  分类筛选下拉框
	 * @param
	 * @return
	 **/
	public function show(){
		//递归所有分类
		$category = new Category();
		$res= $category->getAllData();		
		return $this->success($res);
	}

	/**
	 * 添加分类操作
	 * @param  CategoryRequest  $request
	 * @return json
	 **/
	public function store(CategoryRequest $request){
		// dd($request->all());
		$category = new Category();
		$category->name      = $request->name;
		$category->parent_id = $request->parent_id;
		$category->sort      = $request->sort ?? 0;
		if($category->save()){
			return $this->success($category);
		}else{
			return $this->failed('操作失败');
		}
	}

	/**
	 * 修改分类
	 * @param Request $request
	 * @param int $id
	 * @return 
	 **/
	public function edit(CategoryRequest $request, $id){
		// dd($request->method());
		$category = new Category();
		$tree= $category->getAllData();

		if($request->method() == "GET"){
			// 展示页面
			$res['data'] = Category::find($id);
			$res['tree'] = $tree;
			if($res['data']){
				return $this->success($res);
			}else{
				return $this->failed('操作失败');
			}
		}elseif($request->method() == "POST"){
			// 更新操作
			// dd($request->all());
			$res = Category::find($id);
			$res->name      = $request->name;
			$res->parent_id = $request->parent_id;
			$res->sort      = $request->sort ?? 0;
			if($res->save()){
				return $this->success($res);
			}else{
				return $this->failed('操作失败');
			}
		}
	}

	/**
	 * 软删除
	 * @param Request $request 
	 * @param int $id
	 * @return
	 **/
	public function del(Request $request, $id){
		$res = Category::destroy($id);
		if($res){
			return $this->success("操作成功");
		}else{
			return $this->failed('操作失败');
		}
	}
	

}

