<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuperviseTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'supervise_template';

    public $timestamps = true;
    protected $datas = ['deleted_at'];

    protected $fillable = ['content','created_at','updated_at'];
}
