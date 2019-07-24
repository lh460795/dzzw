<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/27
 * Time: 9:46
 */
namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller{

    public function unitSearch(Request $request){

        try{

            $units = Unit::select(['id','name','alias_name'])->filter($request->all())->get();

            return $this->respond($units);

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }
    }

}