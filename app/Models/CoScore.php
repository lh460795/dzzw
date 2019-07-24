<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;


class CoScore extends Model
{
    protected $table = 'wh_co_score';
    protected $fields_all;
//    protected $guarded = [];
    protected $fillable = [
        'uid', 'units_id', 'cid', 'pid', 'rg_score', 'remark', 'month', 'year', 'addtime', 'update_num',
    ];
    public static function generateData(Request $request)
    {
        $query = Self::where(['year' => $request->input('year',date('Y'))]);

        if($request->input('unit_id')) {
            $query->where(['units_id' => $request->input('unit_id')]);
        }

        $data = $query->with([
            'unit:id,name',
            'project:id,pname',
            'custom:id,p_value,m_value'
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


        foreach ($data as $key => $value)
        {
            $structer[$key]['unit_name'] = Unit::find($value[$key]['units_id'])->name;
            //根据 单位/项目/节点 分组
            foreach($value as $k => $v) {
                $structer[$key]['projects'][$v['pid']]['customs'][$v['cid']]['c_name'] = !empty($v['custom'] ) ? $v['custom']['p_value'].'---'.$v['custom']['m_value'] : 'custom';
                $structer[$key]['projects'][$v['pid']]['customs'][$v['cid']]['score'][$v['month']] = $v['rg_score'];
                $structer[$key]['projects'][$v['pid']]['pname'] = !empty($v['project'] ) ? $v['project']['pname'] : 'mock';
            }
            $structer[$key]['projects'] = array_values($structer[$key]['projects']);
        }

        //补全数据,处理下标
        foreach ($structer as &$value)
        {
            foreach ($value['projects'] as &$project)
            {
                $project['customs'] = array_values($project['customs']);
                foreach ($project['customs'] as &$custom)
                {
                    $monthes = array_keys($custom['score']);
                    for($i=1;$i<=12;$i++)
                    {
                        if(!in_array($i,$monthes))
                        {
                            $custom['score'][$i] = '';
                        }
                    }
                    ksort($custom['score']);
                    $custom['score'] = array_values($custom['score']);
                }
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

    public function custom()
    {
        return $this->belongsTo(ProjectPlanCustom::class,'cid','id');
    }
}
