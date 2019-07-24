<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 分类模型
 *
 */
class Category extends Model {

	use SoftDeletes;

	protected $table = 'category';  // 关联模型

	protected $primaryKey = 'id';  // 主键

	protected $dates = ['deleted_at'];  // 软删除


	public function getAllData(){
		$data = $this->orderBy('sort', 'asc')->get()->toArray();

		return $this->getTree($data);
	}

	/**
     * 递归实现无限极分类
     * @param $array 分类数据
     * @param $pid 父ID
     * @param $level 分类级别
     * @return $list 分好类的数组 直接遍历即可 $level可以用来遍历缩进
     */
    public function getTree($array, $pid =0, $level = 0){
        //声明静态数组,避免递归调用时,多次声明导致数组覆盖
        static $list = [];
        foreach ($array as $key => $value){
            //第一次遍历,找到父节点为根节点的节点 也就是pid=0的节点
            if ($value['parent_id'] == $pid){
                //父节点为根节点的节点,级别为0，也就是第一级
                $value['level'] = $level;
                //把数组放到list中
                $list[] = $value;
                //把这个节点从数组中移除,减少后续递归消耗
                unset($array[$key]);
                //开始递归,查找父ID为该节点ID的节点,级别则为原级别+1
                $this->getTree($array, $value['id'], $level+1);
            }
        }
        return $list;
    }

    // 获取分类名
    public static function getCategoryName($category_id){
        return Category::where(['id' => $category_id])->value('name');
    }
}