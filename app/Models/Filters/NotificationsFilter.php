<?php namespace App\Models\Filters;

use EloquentFilter\ModelFilter;

class NotificationsFilter extends ModelFilter
{
    /**
    * Related Models that have ModelFilters as well as the method on the ModelFilter
    * As [relationMethod => [input_key1, input_key2]].
    *
    * @var array
    */
    public $relations = [];
    protected $drop_id = false;

    public function content($value)
    {
        return $this->whereLike('content', $value);
    }

    public function type($type_id)
    {
        return $this->where('type_id', $type_id);
    }

    public function status($status)
    {
        return $this->where('status', $status);
    }

    public function department($department_id) {
        return $this->where('department_id', $department_id);
    }

    public function user_id() {
        return $this->where('notifiable_id', \Auth::guard('api')->id());
    }

    public function start($start) {
        return $this->where('created_at','>=', $start);
    }

    public function end($end) {
        return $this->where('created_at', '<=', $end);
    }
}
