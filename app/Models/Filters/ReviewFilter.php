<?php namespace App\Models\Filters;

use EloquentFilter\ModelFilter;

class ReviewFilter extends ModelFilter
{
    public $relations = [];

    protected $camel_cased_methods = false;



    public function range_time($value)
    {
        $value = [
            $value[0].' 00:00:00',
            $value[1].' 23:59:59'
        ];
        return $this->whereBetween('created_at',$value);
    }



}
