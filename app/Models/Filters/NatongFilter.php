<?php namespace App\Models\Filters;

use EloquentFilter\ModelFilter;

class NatongFilter extends ModelFilter
{
    public $relations = [];

    //针对下划线id
    protected $drop_id = false;
    //加了这个属性  过滤器才能生效
    protected $camel_cased_methods = false;

    public function is_incor($is_incor = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14])
    {
        //判断传递过来的 纳统查询条件   未纳统   应统未统    已纳统
        $path = request()->path();
        $result = explode('/', $path);
        $actions = end($result);

        if ($actions == "quanshi" || $actions == "receive" || $actions == "exportQuanshi") {
            return '';
        } else {
            return $this->whereIn('is_incor', $is_incor);
        }
    }

    //项目单位id
    public function units($units = [69, 70, 71, 72, 73, 74, 75])
    {
//        return $this->whereHas('Project', function ($query) use ($corp_id) {
//            return $this->whereIn('corp_id', $corp_id);
//        });
        //2->孝南69  3->汉川70  4->应城71  5->云梦75   6->安陆72  7->大悟73  8-> 孝昌74

        if ($units[0] == 2) {
            $units[0] = 69;
        } else if ($units[0] == 3) {
            $units[0] = 70;
        } else if ($units[0] == 4) {
            $units[0] = 71;
        } else if ($units[0] == 5) {
            $units[0] = 75;
        } else if ($units[0] == 6) {
            $units[0] = 72;
        } else if ($units[0] == 7) {
            $units[0] = 73;
        } else if ($units[0] == 8) {
            $units[0] = 74;
        }

        return $this->whereHas('Project', function ($query) use ($units) {
            return $query->whereIn('units_id', $units);
        });
    }

    //项目类型
    public function type($type = [1, 2, 3, 4])
    {
//        $this->related('Project', function($query) use ($type) {
//            return $query->whereIn('type', $type);
//        });

        return $this->whereHas('Project', function ($query) use ($type) {
            return $query->whereIn('type', $type);
        });
    }

    //入库后的项目状态
    public function pro_status($pro_status)
    {
        if ($pro_status) {
            return $this->whereHas('Project', function ($query) use ($pro_status) {
                return $query->where('pro_status', $pro_status);
            });
        } else {
            return $this->whereHas('Project', function ($query) use ($pro_status) {
                return $query->whereNotIn('pro_status', ['5']);
            });
        }
    }


    //项目名称或者项目id模糊匹配
    public function pname($pname)
    {

        return $this->whereHas('Project', function ($query) use ($pname) {
            $query->where('pname', 'like', '%' . $pname . '%')->orwhere('id', 'like', '%' . $pname . '%');
        });

    }

}
