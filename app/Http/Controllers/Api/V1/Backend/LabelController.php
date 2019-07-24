<?php

namespace App\Http\Controllers\Api\V1\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\LabelRequest;
use App\Models\Label;


/**
 * 标签管理控制器
 */
class LabelController extends Controller {
	
	/**
	 *  标签展示页面
	 **/
	public function index(){
		$res = Label::getAllData();
		return $this->success($res);
	}

	/**
	 *  标签添加
	 *  @param LabelRequest $request
	 *  @return
	 **/
	public function add(LabelRequest $request){
		$model = new Label();
		$model->name = $request->name;
		$model->sort = $request->sort ?? 0;
		if($model->save()){
			return $this->success($model);
		}else{
			return $this->failed('操作失败');
		}
	}

	/**
	 *  标签修改
	 *  @param LabelRequest $request  
	 *  @param int $id
	 *  @return
	 **/
	public function edit(LabelRequest $request, $id){
		if($request->method() == 'GET'){
			// 修改页面显示
			$label = Label::find($id);
			if($label){
				return $this->success($label);
			}else{
				return $this->failed('操作失败');
			}
		}elseif($request->method() == 'POST'){
			// 更新操作
			$label = Label::find($id);
			$label->name = $request->name;
			$label->sort = $request->sort ?? 0;
			if($label->save()){
				return $this->success($label);
			}else{
				return $this->failed('操作失败');
			}
		}
	}

	/**
	 *  标签删除
	 *  @param LabelRequest $request
	 *  @param int $id
	 *  @return
	 **/
	public function del(LabelRequest $request, $id){
		$res = Label::destroy($id);
		if($res){
			return $this->success('操作成功');
		}else{
			return $this->failed('操作失败');
		}
	}

}