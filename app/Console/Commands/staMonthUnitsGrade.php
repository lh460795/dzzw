<?php

namespace App\Console\Commands;

use App\Models\CoScore;
use App\Models\LastScore;
use App\Models\MonthScore;
use App\Models\Project;
use App\Models\Sponsor;
use App\Models\Unit;
use App\Models\UnitAvg;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class staMonthUnitsGrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sta:units';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '单位评分';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    private $_pid_array;

    public function __construct()
    {
        parent::__construct();
        $this->_pid_array = [Project::DJ_1, Project::DJ_2, Project::DJ_3]; //排除冻结项目 780   1027    184
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $now_m = date('n', time()); //当前月份
        //$now_m = 5;
        $now_y = date('Y', time()); //当前年份
//        $now_y = 2019;
        $now = date('Y-m-d', time()); //当前时间
        //$now = "2019-05-01";
        $day_arr = getthemonth($now);
        $firstday = $day_arr[0]; //当月第一天
        $month_sql = 0; //插入评分表用
        if ($now_m == 1) {
            //如果当前是1月份 取去年 12月
            $year = $now_y - 1;
            $month_sql = 12;
        } elseif (($now_m != 1) && ($now_m <=12) ) {
            $year = $now_y;
            $month_sql = $now_m - 1;
        }
        //当月第一天 执行 $now == $firstday
        if ($now == $firstday) {
            //月度评分关联项目数组 主办评分有用
            $max_id =collect(DB::select("SELECT MAX(id) as max_id from wh_month_score where `month`={$month_sql} and `year`={$year} GROUP BY pid"))
                ->pluck('max_id')->toArray() ?? null;
            $score_list =
                MonthScore::from('wh_month_score as s')
                ->leftJoin('project as p','p.id','s.pid')
                ->leftJoin('units as c','c.id','p.units_id')
                ->select('s.pid','s.x_score','s.month','s.year','p.pname','p.units_id','c.name','c.alias_name')
                ->whereIn('s.id',$max_id)
                ->orderBy('s.pid','asc')
                ->get()
                ->toArray();
            //dd($score_list);
            //协办记录表
            $xb_list = CoScore::select('id','units_id','rg_score')->get()->toArray();
            $item_list = [];
            $xb_item_list = [];
            $zb_item_list = [];
            $item_list = collect($score_list)->groupBy('units_id')->toArray();//用units_id 作为下标 生成新数组
            $xb_item_list = collect($xb_list)->groupBy('units_id')->toArray();//协办按单位下标列表
            //dump($xb_item_list);
            foreach ($item_list as $key => $item) {
                if (is_array($item)) {
                    foreach ($item as $t => $v) {
                        //在冻结项目范围内 跳出循环
                        if (in_array($v['pid'], $this->_pid_array)) {
                            continue;
                        }
                        //查询主办评分表是否有记录
                        if ($v['units_id']) {
                            $score_s = Sponsor::whereRaw("units_id={$v['units_id']} and pid={$v['pid']} and `month`={$v['month']} and `year`={$v['year']}")
                                ->select('id')
                                ->first();
                            if ($score_s) {
                                continue;
                            }
                            $data_add['units_id'] = $v['units_id'];
                            $data_add['pid'] = $v['pid'];
                            $data_add['score'] = $v['x_score'];
                            $data_add['month'] = $v['month'];
                            $data_add['year'] = $v['year'];
                            $data_add['addtime'] = time();
                            //插入主办评分表
                            $result_info = Sponsor::create($data_add);
                        }
                    }
                }
            }
            //主办得分记录
            $zb_list = Sponsor::whereRaw("`month`={$month_sql} and `year`={$year}")
                ->select('id','units_id','pid','score','month','year')
                ->get()
                ->toArray();
            $zb_item_list = collect($zb_list)->groupBy('units_id')->toArray();//协办按单位下标列表
            //计算主办得分
            $p_count = 0;
            $zb_score = 0; //主办得分
            foreach ($zb_item_list as $units_id => $item_zb) {
                $p_count = count($item_zb); //项目个数
                $total = 0; //项目总分
                foreach ($item_zb as $t => $v) {
                    $total += $v['score'];
                }
                //主办得分 本单位项目总分/项目个数
                $zb_score = round($total / $p_count, 2);
                //更新单位表 主办得分字段
                $save_score['z_score'] = $zb_score;
                Unit::where('id',$units_id)->update($save_score);
            }
            //计算协办单位得分
            foreach ($xb_item_list as $k => $item_xb) {
                $x_score = 0; //协办分值
                $xb_count = 0; //协办个数
                $co_info = CoScore::whereRaw("units_id={$k} and `month`={$month_sql} and `year`={$year}")
                    ->get()->toArray();
                //有协办记录才更新分值
                if ($co_info) {
                    $xb_count = count($co_info);
                    foreach ($co_info as $k_co => $v_co) {
                        $x_score += $v_co['rg_score'];
                    }
                    $xb_score = round($x_score / $xb_count, 2); //协办考核得分
                    //更新单位表 协办得分字段
                    $xieban_score['x_score'] = $xb_score;
                    Unit::where('id',$k)->update($xieban_score);
                }
            }
            //计算单位最终得分 主办*0.8 + 协办* 0.2
            $corp_list = Unit::select('id','z_score','x_score','t_score','name','alias_name')
                ->orderBy('id')->get()->toArray();
            foreach ($corp_list as $ke => $va) {
                if (($va['z_score'] != '') && ($va['x_score'] != '')) {
                    $t_score = ($va['z_score'] * 0.8) + ($va['x_score'] * 0.2);
                } elseif ($va['z_score'] == '') {
                    //主办为空
                    $t_score = $va['x_score'];
                } elseif ($va['x_score'] == '') {
                    //协办为空
                    $t_score = $va['z_score'];
                } else {
                    $t_score = '';
                }
                $save['t_score'] = $t_score;
                Unit::where('id',$va['id'])->update($save);
            }
            //dump($corp_list);exit;
            $new_corp = Unit::select('id','z_score','x_score','t_score','name','alias_name','dis')
                ->orderBy('id')->get()->toArray();
            //插入考核得分历史记录
            foreach ($new_corp as $key => $value) {
                //查询考核最终得分是否有记录
                $last_info = LastScore::whereRaw("units_id={$value['id']} and `month`={$month_sql} and `year`={$year}")
                    ->first();
                if (!$last_info) {
                    $last_add['units_id'] = $value['id'];
                    $last_add['z_score'] = $value['z_score'];
                    $last_add['x_score'] = $value['x_score'];
                    $last_add['t_score'] = $value['t_score'];
                    $last_add['month'] = $month_sql;
                    $last_add['year'] = $year;
                    $last_add['addtime'] = time();
                    $last_result = LastScore::create($last_add);
                }
                //添加考评得分平均分 及 日常定量考核得分
                $corp_svg_info = UnitAvg::whereRaw("units_id={$value['id']} and `year`={$year}")->first();
                $avgScore = LastScore::whereRaw("units_id={$value['id']} and `year`={$year}")->avg('t_score');
                $avgScore = round($avgScore, 3);
//              $rc_score = $avgScore * 0.8; //日常定量考核得分
                //0108
                if ($value['dis'] == '1') {
                    //1、县/区级；2、市级', 县市区 * 0.55 市 * 0.6
                    $rc_score = $avgScore * 0.55; //日常定量考核得分
                } else {
                    $rc_score = $avgScore * 0.6; //日常定量考核得分
                }

                $rc_score = round($rc_score, 3);
                if (!$corp_svg_info) {
                    $svg_add['units_id'] = $value['id'] ?? 0;
                    $svg_add['year'] = $year;
                    $svg_add['svg_score'] = $avgScore; //平均分
                    $svg_add['rc_score'] = $rc_score;
                    $svg_add['addtime'] = time();
                    $svg_result = UnitAvg::create($svg_add);
                } else {
                    //更新数据
                    $update_score['svg_score'] = $avgScore;
                    $update_score['rc_score'] = $rc_score;
                    UnitAvg::whereRaw("units_id={$value['id']} and `year`={$year}")->update($update_score);
                }
            }
        }
        echo 'now=' . date('Y-m-d H:i:s', time());
    }

    //调试log
    protected function test_log($data){
        file_put_contents('test.log', $data . PHP_EOL);
    }
}
