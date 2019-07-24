<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/4
 * Time: 15:42
 */
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use EloquentFilter\Filterable;

class Unit extends model{

    use Filterable;

    protected $table = 'units';

    //TODO 迁移数据时关闭
    public $timestamps = false;
    protected $fields_all;

    public function childUnit()
    {
        return $this->hasMany('App\Models\Unit', 'parent_id', 'id');
    }

    public function allChildrenUnits()
    {
        return $this->childUnit()->with('allChildrenUnits');
    }

    //根据单位ID 返回单位信息
    public function get_unit($unitid) {
        return $this->findOrFail($unitid)->toArray();
    }

    // 获取单位名（简称）
    public static function getName($unitid){
        return self::where(['id' => $unitid])->value('name');
    }

    // 获取单位名（全称）
    public static function getAliasName($unitid){
        return self::where(['id' => $unitid])->value('alias_name');
    }
    // 获取单位名集合（简称）2,3 转换成 单位1,单位2
    public static function getNames($unitids,$separation=','){
        $array = explode($separation,$unitids);
        $name = '';
        $item_array = [];
        foreach ($array as $k=>$val){
            $name .=self::getName($val).$separation;
        }
        $name = rtrim($name,$separation);
        $item_array = explode($separation,$name);
        return $item_array;
    }
    //查询id name 按sort字段排序
    public static function getAll(){
        return self::select('id','name')->orderBy('sort','asc')->get()->toArray();//单位列表
    }

    public function corp(){

        return $this->hasMany('App\Models\Corp','units_id','id');

    }

    //对xieban字段 进行 处理  数据迁移用
    public static function getXieban($xiebans,$separation='|'){
        $array = explode($separation,$xiebans);
        $name = '';
        foreach ($array as $k=>$val){
            $xieban_id = self::where('name',$val)->value('id') ?? '';
            $name .=$xieban_id.$separation;
        }
        $name = rtrim($name,$separation);
        return $name;
    }
}
