<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends BaseModel
{
    //use SoftDeletes;

    protected $fields_all;

    //TODO 迁移数据时关闭
    public $timestamps = false;

    public function project()
    {
        return $this->belongsToMany(Project::class, 'project_tags');
    }

}
