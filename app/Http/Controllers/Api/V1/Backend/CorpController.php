<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/11
 * Time: 10:31
 */
namespace App\Http\Controllers\Api\V1\Backend;

use App\Models\Corp;
use Illuminate\Http\Request;
use App\Http\Requests\CorpCreateRequest;
use App\Http\Requests\CorpUpdateRequest;
use App\Http\Controllers\Api\Controller;

class CorpController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function info()
    {
        $corps = $this->tree();
        return $this->respond($corps);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CorpCreateRequest $request)
    {
        try{
            $data = $request->all();
            //顶级架构level为1，子架构level为父架构加1
            if($data['parent_id'] == 0) {
                $data['level'] = 1;
            }else{
                $corp = Corp::find($data['parent_id']);
                $data['level'] = $corp->level + 1 ;
            }
            if (Corp::create($data)){
                return $this->message('添加成功',200);
            }
            return $this->failed('系统错误',500);
        }catch (\Exception $e){
            return $this->failed($e->getMessage(),500);
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function getUserCorp($id)
    {
        $corp = Corp::findOrFail($id);
        $corps = $this->tree();
        $data=[
            'userCorp'=>$corp,
            'corps'=>$corps
        ];
        return $this->respond($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CorpUpdateRequest $request, $id)
    {
        try{
            $corp = Corp::findOrFail($id);
            $data = $request->all();
            if ($corp->update($data)){
                return $this->message('更新成功',200);
            }
            return $this->failed('系统错误',500);
        }catch(\Exception $e){
            return $this->failed($e->getMessage(),500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        try{
            $id = $request->get('id');
            $corp = Corp::find($id);
            $corp->delete();
            if ($corp->trashed()){
                return $this->message('删除成功',200);
            }
            return $this->failed('删除失败',500);
        }catch(\Exception $e){
            return $this->failed($e->getMessage(),500);
        }

    }

    public function data()
    {
        $res = Corp::getCorps();
        $res = $this->sortByWeight($res);
        $data = [
            'code' => 0,
            'msg'   => 'ok',
            'data'  => $res
        ];
        return $this->respond($data);
    }

    /*
     *根据权重排序
     */
    public function sortByWeight($corps)
    {
        $tmp = $data = [];
        foreach ($corps as $key => $corp)
        {
            $tmp[$corp['level']][] = $corp;
            if(count($tmp[$corp['level']]) > 1) {
                $tmp[$corp['level']] = $this->arraySort($tmp[$corp['level']],'weight');
            }
        }

        //转一维数组
        $tmp = array_reduce($tmp, function ($result, $value) {
            return array_merge($result, array_values($value));
        }, []);

        return $tmp;
    }

    public function tree($type=1,$list=[], $pk='id', $pid = 'parent_id', $child = '_child', $root = 0)
    {
        if (empty($list)){
            $list = Corp::getCorps();
        }
        // 创建Tree
        $tree = array();
        if(is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId =  $data[$pid];
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                    $tree = $this->arraySort($tree,'weight');
                }else{
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                        $parent[$child] = $this->arraySort($parent[$child],'weight');
                    }
                }
            }
        }
        return $tree;
    }

    /**
     * 二维数组根据某个字段排序
     * @param array $array 要排序的数组
     * @param string $keys   要排序的键字段
     * @param string $sort  排序类型  SORT_ASC     SORT_DESC
     * @return array 排序后的数组
     */
    public function arraySort($array, $keys, $sort = SORT_ASC) {
        $keysValue = [];
        foreach ($array as $k => $v) {
            $keysValue[$k] = $v[$keys];
        }
        array_multisort($keysValue, $sort, $array);
        return $array;
    }
}