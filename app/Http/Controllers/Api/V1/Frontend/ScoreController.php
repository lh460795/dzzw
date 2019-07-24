<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Models\CoScore;
use App\Models\LastScore;
use App\Models\Sponsor;
use App\Models\Unit;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ScoreController extends Controller
{
    /**
     * 考核对象得分
     * @param Request $request
     * @return array
     */
    public function lastScore(Request $request)
    {
        $data  = LastScore::generateData($request);
        return $data;
    }

    /**
     * 主办单位得分
     * @param Request $request
     * @return mixed
     */
    public function sponsorScore(Request $request)
    {
        $data = Sponsor::generateData($request);
        return $data;
    }

    /**
     * 协办单位得分
     * @param Request $request
     * @return array
     */
    public function coScore(Request $request)
    {
        $data = CoScore::generateData($request);
        return $data;
    }

    public function getUnits()
    {
        $data = Unit::pluck('name','id');
        return $data;
    }
}
