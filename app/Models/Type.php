<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 项目类型模型
 */
class Type extends Model {

	protected $table = 'type';

	protected $primaryKey = 'id';

	/**
     * 根据type id 获取 type 名称
     **/
	public static function get_type_name($type){
		return self::where(['id' => $type])->value('name');
	}
}