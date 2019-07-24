<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/8
 * Time: 17:42
 */
namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use App\Models\Project;

class DataStatisticsController extends Controller{

    public function projectStatistics(Request $request){

        try{

            $project=Project::selectRaw('type,count(id) as num,sum(amount_now) as sum_amount_now,sum(amount) as sum_amount')->filter($request->all())->groupBy('type')->get()->toArray();

            if(empty($project)) return [];

            foreach($project as $value){

                $projectStatistics[$value['type']]['name']=config('project.typeInfo')[$value['type']];

                $projectStatistics[$value['type']]['value']=$value['num'];

                $amountStatistics[$value['type']]['name']=config('project.typeInfo')[$value['type']];

                $amountStatistics[$value['type']]['value']=$value['sum_amount_now'];

                $totalAmountStatistics[$value['type']]['name']=config('project.typeInfo')[$value['type']];

                $totalAmountStatistics[$value['type']]['value']=$value['sum_amount'];

            }

            $data=[array_values($projectStatistics),array_values($amountStatistics),array_values($totalAmountStatistics)];

            return $this->respond($data);

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }
    }

    public function projectTableStatistics(Request $request){

        try{

            $project=Project::selectRaw('type,count(id) as num,sum(amount_now) as sum_amount_now,sum(amount) as sum_amount')->filter($request->all())->groupBy('type')->get()->toArray();

            if(empty($project)) return [];

            foreach($project as $value){

                $data[$value['type']]['title']=config('project.typeInfo')[$value['type']];

                $data[$value['type']]['data']=[

                    $value['num'],

                    (float) $value['sum_amount_now'],

                    (float) $value['sum_amount']

                ];
            }

            return $this->respond(array_values($data));

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }

    }

    public function investmentStatistics(Request $request)
    {

        $Min=Project::selectRaw('type,count(id) as num')->filter($request->all())->whereBetween($request->amount_type,[1,10])->groupBy('type')->get()->toArray();

        foreach ($Min as $value){
            $investmentMin[$value['type']]=$value['num'];
        }

        $Max=Project::selectRaw('type,count(id) as num')->filter($request->all())->where($request->amount_type,'>',10)->groupBy('type')->get()->toArray();

        foreach ($Max as $value){
            $investmentMax[$value['type']]=$value['num'];
        }

        foreach(config('project.typeInfo') as $key=>$value){
            !isset($investmentMin[$key])&&$investmentMin[$key]=0;
            !isset($investmentMax[$key])&&$investmentMax[$key]=0;
        }

        return $this->respond([

            array_values(config('project.typeInfo')),

            array_values($investmentMin),

            array_values($investmentMax)

        ]);

    }

    public function investmentTableStatistics(Request $request)
    {
        $data=[];

        foreach(config('project.typeInfo') as $key=>$value){

            $data[$key]['title']=$value;

            $data[$key]['data']=[

                Project::filter($request->all())->whereBetween('amount_now',[1,10])->where('type',$key)->count('id'),

                Project::filter($request->all())->where([
                    ['amount_now','>',10],
                    ['type',$key]
                ])->count('id'),

                Project::filter($request->all())->whereBetween('amount',[1,10])->where('type',$key)->count('id'),

                Project::filter($request->all())->where([
                    ['amount','>',10],
                    ['type',$key]
                ])->count('id')

            ];
        }

        return $this->respond(array_values($data));
    }
}