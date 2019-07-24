<?php
/**
 * 资源处理基类
 */
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class BaseResource extends Resource
{
    protected $withoutFields = [];

    private $hide = true;

    //protected $type = 'default';

//    public function type(string $request)
//    {
//        $this->type = $request;
//        return $this;
//    }

    public function hide(array $fields)
    {
        $this->withoutFields = $fields;
        return $this;
    }

    public function show(array $fields)
    {
        $this->withoutFields = $fields;
        $this->hide = false;
        return $this;
    }

    protected function filterFields($array)
    {
        if (!$this->hide) {
            return collect($array)->only($this->withoutFields)->toArray();
        }
        return collect($array)->except($this->withoutFields)->toArray();
    }
}
