<?php namespace App\Models\Filters;

use EloquentFilter\ModelFilter;

class CommentFilter extends ModelFilter
{
    public $relations = [];

    protected $camel_cased_methods = false;


    public function pro_type($type)
    {
        return $this->whereHas('Project', function ($query) use ($type) {
            return $query->where('type', $type);
        });
    }

    public function pro_status($pro_status)
    {
        if ($pro_status) {
            return $this->whereHas('Project', function ($query) use ($pro_status) {
                return $query->where('pro_status', $pro_status);
            });
        } else {
            return $this->whereHas('Project', function ($query) use ($pro_status) {
                return $query->whereNotIn('pro_status', [5]);
            });
        }
    }

    public function is_push($value)
    {
        return $this->whereHas('Project', function ($query) use ($value) {
            return $query->where('is_push', $value);
        });
    }

    public function range_time($value)
    {
        $value = [
            $value[0].' 00:00:00',
            $value[1].' 23:59:59'
        ];
        return $this->whereBetween('created_at',$value);
    }


    public function pname($pname)
    {
        return $this->whereHas('Project', function ($query) use ($pname) {
            $query->where('pname', 'like', '%' . $pname . '%')
                  ->orwhere('id', 'like', '%' . $pname . '%')
                  ->orWhereHas('unit',function ($q) use($pname) {
                      $q->where('name', 'like', '%' . $pname . '%');
                  });
        });

    }

}
