<?php

namespace App\Models;


use EloquentFilter\Filterable;

class Notifications extends BaseModel
{
    //use SoftDeletes;
    use Filterable;
    protected $table = 'notifications';
    protected $fields_all;




}
