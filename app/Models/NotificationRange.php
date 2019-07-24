<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationRange extends Model
{
    use SoftDeletes;

    protected $table = 'notification_range';

    public $timestamps = true;
    protected $datas = ['deleted_at'];
}
