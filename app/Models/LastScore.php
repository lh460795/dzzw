<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LastScore extends Model
{
    protected $table = 'wh_last_score';
    protected $fields_all;
//    protected $guarded = [];
    protected $fillable = [
        'units_id', 'z_score', 'x_score', 't_score', 'month', 'year', 'addtime'
    ];
    public static function generateData(Request $request)
    {
        $query = Self::where(['year' => $request->input('year',date('Y'))]);
        if($request->input('unit_id') && $request->input('unit_id') != 0) {
            $query->where(['units_id' => $request->input('unit_id')]);
        }
        $data = $query->with('unit:id,name')
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

        $tmp = [];
        //按table结构组装数据
        foreach ($data as $key => $value) {
            $monthes = array_column($value,'month');
            $z_socre = array_combine($monthes,array_column($value,'z_score'));
            $x_socre = array_combine($monthes,array_column($value,'x_score'));
            $t_socre = array_combine($monthes,array_column($value,'t_score'));
            for($i=1;$i<=12;$i++)
            {
                if(!in_array($i,$monthes)) {
                    $z_socre[$i] = '';
                    $x_socre[$i] = '';
                    $t_socre[$i] = '';
                }
            }
            ksort($z_socre);
            ksort($x_socre);
            ksort($t_socre);
            $tmp[$key]['unit_name'] = $value[0]['unit']['name'];
            $tmp[$key]['data'] = [
                'z' => array_values($z_socre),
                'x' => array_values($x_socre),
                't' => array_values($t_socre)
            ];
        }

        $res = [
            'current_page' => $page,
            'data' => array_values($tmp),
            'last_page' => $last_page,
            'total' => $total
        ];
        return $res;
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class,'units_id','id');
    }
}
