<?php

namespace App\Http\Controllers\Api\V1\Backend;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use App\Models\Category;
use App\Models\Label;
use App\Http\Requests\Api\ArticleRequest;
use App\Models\Article;

/**
 * 文章管理控制器
 **/
class ArticleController extends Controller {
    /**
     * 文章列表页面
     **/
    public function list(){
    	$article = Article::getAllData();
        return $this->success($article);
    }   

    /**
     * 文章展示页面
     * @param ArticleRequest $request
     * @param int $id
     **/
    public function show(ArticleRequest $request, $id){
    	$res = Article::find($id);
        if($res){
            $res->category_name = Category::getCategoryName($res->category_id);
            $res->label_name    = Label::getLabelName($res->label_id);
        	return $this->success($res);
        }else{
            return $this->failed('操作失败');
        }
    }

    /**
     * 文章增加页面
     **/
    public function create(){
    	$model = new Category();
    	$category = $model->getAllData();
    	$label    = Label::getAllData();
    	$res['category'] = $category;
    	$res['label']    = $label;
    	return $this->success($res);
    }

    /**
     * 文章增加操作
     * @param ArticleRequest $request
     **/
    public function store(ArticleRequest $request){
    	$data = [
    		'uid'         => $request->uid,
    		'category_id' => $request->category_id ?? 0,
    		'label_id'    => $request->label_id ?? 0,
    		'title'       => $request->title,
    		'keyword'     => $request->keyword,
    		'description' => $request->description,
    		'content'     => $request->content,
    		'sort'        => $request->sort ?? 0
    	];
    	$res = Article::create($data);
    	return $this->success($res);
    }

    /**
     * 文章修改页面
     * @param ArticleRequest $request
     * @param int $id
     **/
    public function edit(ArticleRequest $request, $id){
    	$model = new Category();
    	$category = $model->getAllData();
    	$label    = Label::getAllData();
    	$res['category'] = $category;
    	$res['label']    = $label;
    	$article = Article::find($id);
    	if($article){
    		$res['article'] = $article;
    		return $this->success($res);
    	}else{
    		return $this->failed('操作失败');
    	}    	
    }

    /**
     * 文章修改操作
     * @param ArticleRequest $request
     * @param int $id
     **/
    public function update(ArticleRequest $request, $id){
    	$article = Article::find($id);
    	if($article){
    		$data = [
	    		'uid'         => $request->uid,
	    		'category_id' => $request->category_id ?? 0,
	    		'label_id'    => $request->label_id ?? 0,
	    		'title'       => $request->title,
	    		'keyword'     => $request->keyword,
	    		'description' => $request->description,
	    		'content'     => $request->content,
	    		'sort'        => $request->sort ?? 0
	    	];
	    	$res = Article::where(['id' => $id])->update($data);
	    	if($res){
	    		return $this->success($res);
	    	}else{
	    		return $this->failed('操作失败');
	    	}
    	}else{
    		return $this->failed('操作失败');
    	}
    }

    /**
     * 文章删除操作
     * @param ArticleRequest $request
     * @param int $id
     **/
    public function del(ArticleRequest $request, $id){
    	$res = Article::destroy($id);
    	if($res){
			return $this->success("操作成功");
		}else{
			return $this->failed('操作失败');
		}
    }

    /**
     * 文章访问量
     * @param int $id
     */
    public function increase(ArticleRequest $request, $id){
    	$res = Article::find($id);
    	$res->click++;
    	$res->save();
    }

}
