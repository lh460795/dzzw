<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReadLog extends Model
{
    const COMMENT_TYPE = 1;
    const REVIEW_TYPE = 2;

    protected $table = 'read_log';
    protected $fillable = ['user_id','relation_id','type'];
}
