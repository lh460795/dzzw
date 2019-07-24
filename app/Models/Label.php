<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * 标签管理模型
 */
class Label extends Model {
	use SoftDeletes;

	protected $table = 'label';

	protected $primaryKey = 'id';

	protected $dates = ['deleted_at']; 

	// 获取所有数据
	public static function getAllData(){
		return Label::orderBy('sort', 'asc')->get()->toArray();
	}

	// 获取标签名
	public static function getLabelName($label_id){
		return Label::where(['id' => $label_id])->value('name');
	}
}