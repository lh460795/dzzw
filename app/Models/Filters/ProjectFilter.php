<?php
namespace App\Models\Filters;

use EloquentFilter\ModelFilter;
use http\Env\Request;

class ProjectFilter extends ModelFilter
{
    /**
     * Related Models that have ModelFilters as well as the method on the ModelFilter
     * As [relationMethod => [input_key1, input_key2]].
     *
     * @var array
     */
    protected $drop_id = false;
    //加了这个属性  过滤器才能生效
    protected $camel_cased_methods = false;

    public $relations = [];

    public function id($id)
    {
        return $this->where('id', $id);
    }

    public function pname($value)
    {
        return $this->whereLike('pname', $value);
    }

    public function type($type_id)
    {
        return $this->where('type', $type_id);
    }


    public function status($status)
    {
        return $this->where('pro_status', $status);
    }

    public function status_name($status_name)
    {
        return $this->where('status_name', $status_name);
    }

    public function status_flow($status)
    {
        return $this->where('status_flow', $status);
    }

    public function uid($uid)
    {
        return $this->where('uid',$uid);
    }

    public function units_id($units_id)
    {
        return $this->where('units_id',$units_id);
    }

    public function time($start, $end) {
        return $this->whereBetween('created_at', [$start, $end]);
    }

    public function area_id($area_id)
    {
        return $this->where('pro_area',$area_id);
    }

    public function start($start) {
        return $this->where('created_at','>=', $start);
    }


    public function end($end) {
        return $this->where('created_at', '<=', $end);
    }

    public function tag($value)
    {
        return $this->whereHas('tag', function ($query) use ($value) {
            return $query->where('tags.id', $value);
        });
    }

    public function is_push($is_push) {
        return $this->where('is_push', $is_push);
    }

    public function is_year($is_year) {
        return $this->where('is_year', $is_year);
    }

    public function is_new($is_new) {
        return $this->where('is_new', $is_new);
    }

    //搜索
    public function search($value) {
        return
            $this->where('pname', 'like', '%' . $value . '%')
                    ->orwhere('id', 'like', '%' . $value . '%')
                    ->orWhereHas('unit',function ($q) use($value) {
                        $q->where('name', 'like', '%' . $value . '%');
                });
    }
    //如果setup()定义了方法，则无论输入如何，都将在任何过滤方法之前调用一次
    public function setup()
    {
        $year = request()->input('year') ?? null;
        return $this->when($year, function ($query) use ($year) {
            return $query->where('year', $year);// 存在时执行
        });
    }

}
