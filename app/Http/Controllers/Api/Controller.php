<?php
namespace App\Http\Controllers\Api;
use App\Models\Permission;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller as BaseController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Traits\Api\ApiResponse;
class Controller extends BaseController
{
    use Helpers, ApiResponse;
    public function errorResponse($statusCode, $message=null, $code=0)
    {
        throw new HttpException($statusCode, $message, null, [], $code);
    }

    public function tree($type=1, $list=[], $pk='id', $pid = 'parent_id', $child = '_child', $root = 0)
    {
        if (empty($list)){
            $list = Permission::where(['type' => $type])->get()->toArray();
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
                }else{
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }

    public function trees($type=1, $list=[], $pk='id', $pid = 'parent_id', $child = 'children', $root = 0)
    {
        if (empty($list)){
            $list = Permission::where(['type' => $type])->get()->toArray();
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
                    $tree[] = & $list[$key];
                }else{
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
            }
        }

        return $tree;
    }

    public function compose($tree) {
        foreach ($tree as $k=>$v) {
            if ( $v['parent_id'] == 0 ) {
                $tree[$k]['navName'] =  $v['display_name'];
                $tree[$k]['navRoute'] = $v['route'];
                unset($tree[$k]['display_name']);
                unset($tree[$k]['route']);
                unset($tree[$k]['itemIcon']);
            }

            if (isset($v['children']) && !empty($v['children'])) {
                foreach ($v['children'] as $k1=>$v1) {
                    $tree[$k]['children'][$k1]['itemName'] =  $v1['display_name'];
                    unset($tree[$k]['children'][$k1]['display_name']);
                    if (isset($v1['children']) && !empty($v1['children'])) {
                        foreach ($v1['children'] as $k2 => $v2) {
                            $tree[$k]['children'][$k1]['children'][$k2]['itemName'] =  $v2['display_name'];
                            unset($tree[$k]['children'][$k1]['children'][$k2]['display_name']);
                        }
                    }
                }
            }

        }

        return $tree;
    }




}