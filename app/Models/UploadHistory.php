<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

class UploadHistory extends Model {
	protected $table = 'uploads_history';
	protected $fields_all;
    //批量赋值字段
    protected $guarded = [];
    public $timestamps = false;

}