<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Models\MonthScore;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\Api\V1\Frontend\RankResource;
class RankController extends Controller
{

    //项目排行榜
    public function projectRank(Request $request)
    {
        $order = empty($request->input('order'))?'asc':$request->input('order');
        $year = $request->input('year')?? null;
        $data  = MonthScore::selectRaw('*, (SELECT count(DISTINCT s_score) 
                                FROM wh_month_score AS b WHERE cast(wh_month_score.s_score as decimal(14,2)) < cast(b.s_score as decimal(14,2)))+1 AS rank')
                         ->when($year, function ($query) use ($year) {
                            return $query->where('year', $year);
                          })
                           ->orderBy('rank', $order)->take(5)->get();
        $data = RankResource::collection($data);
        return $this->success($data);
    }


    //单位排行榜
    public function unitsRank(Request $request)
    {
        $order = empty($request->input('order'))?'asc':$request->input('order');
        $data  = Unit::selectRaw('*, (SELECT count(DISTINCT z_score) 
                                FROM units AS b WHERE cast(units.z_score as decimal(14,2)) < cast(b.z_score as decimal(14,2)))+1 AS rank')
                     ->orderBy('rank', $order)->take(5)->get();

        return $this->success($data);
    }

    //计算排名
    public function rank() {

        $roles = User::find(Auth::guard('api')->id())->roles()->first();

        if (collect($roles)->isNotEmpty()) {
            $roleId = $roles->id;
        } else {
            $roleId = [];
        }

        $roleId = 21;

        $data = [];

        if ( $roleId == 21 || $roleId ==29 ) {
            $data['score'] =  Unit::where('id', Auth::guard('api')->user()->units_id)->value('z_score');
            $data['rank']  =  DB::table('units')->selectRaw('*, (SELECT count(DISTINCT z_score) 
                                FROM units AS b WHERE cast(units.z_score as decimal(14,2)) < cast(b.z_score as decimal(14,2)))+1 AS rank')
                ->where('units.id', Auth::guard('api')->user()->units_id)
                ->where('z_score','!=', null)
                ->orderBy('rank', 'asc')
                ->first();

            if (collect($data['rank'])->isNotEmpty()) {
                $data['rank'] = collect($data['rank'])->toArray();
                $data['rank'] = $data['rank']['rank'];
            }else {
                $data['rank'] = 0;
            }
        }

        return $this->success($data);
    }
}
