<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
class Sponsor extends Model
{
    protected $table = 'wh_sponsor_score';
    protected $fields_all;
//    protected $guarded = [];
    protected $fillable = [
        'units_id','pid', 'score', 'month', 'year', 'addtime',
    ];
    public static function generateData(Request $request)
    {
        $query = Self::where(['year' => $request->input('year',date('Y'))]);

        if($request->input('unit_id')) {
            $query->where(['units_id' => $request->input('unit_id')]);
        }

        $data = $query->with([
            'unit:id,name',
            'project:id,pname'
        ])
            ->orderBy('month','asc')
            ->get()
            ->groupBy('units_id')
            ->toArray();


        $total = count($data);
        $per_page = $request->per_page ?? 5 ;
        $last_page = ceil($total/$per_page);
        $page = $request->page ?? 1;
        $start = ($page - 1) * $per_page;
        $data = array_slice($data,$start,$per_page);

        $structer = [];

        //组装数据 单位/项目
        foreach ($data as $key => $value)
        {
            $structer[$key]['unit_name'] = Unit::find($value[$key]['units_id'])->name;
            foreach ($value as $k => $v)
            {
                $structer[$key]['projects'][$v['pid']]['score'][$v['month']] = $v['score'];
                $structer[$key]['projects'][$v['pid']]['pname'] = !empty($v['project'] ) ? $v['project']['pname'] : 'mock';
            }
            $structer[$key]['projects'] = array_values($structer[$key]['projects']);
        }

        //补全数据,处理下标
        foreach ($structer as &$value)
        {
            foreach ($value['projects'] as &$project)
            {
                $monthes = array_keys($project['score']);
                for($i=1;$i<=12;$i++)
                {
                    if(!in_array($i,$monthes))
                    {
                        $project['score'][$i] = '';
                    }
                }
                ksort($project['score']);
                $project['score'] = array_values($project['score']);
            }
        }

        $res = [
            'current_page' => $page,
            'data' => array_values($structer),
            'last_page' => $last_page,
            'total' => $total
        ];

        return $res;
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class,'units_id','id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class,'pid','id');
    }
}
