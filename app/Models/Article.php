<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 文章管理模型
 **/
class Article extends Model {
	use SoftDeletes;
    
    protected $table = 'article';

    protected $primaryKey = 'id';

    protected $guarded = [];  // 让所有属性都可批量赋值

    protected $dates = ['deleted_at'];

    public static function getAllData(){
    	return Article::orderBy('sort', 'asc')->get()->toArray();
    }
    
}
