<?php

namespace App\Console\Commands;


use App\Models\MonthScore;
use App\Service\ScoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class staProjectGrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sta:project';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '项目月度评分';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(ScoreService $scoreService)
    {

        //插入月度评分表
        $sqls = $scoreService->computeScore() ?? [];
        if (!empty($sqls)) {
            foreach ($sqls as $k =>$val){
                //插入月度评分记录
                MonthScore::create($val);
            }
        }
        //更新主表字段 取最大id 系统评分大于0的 数据
        $max_id =collect(DB::select('SELECT MAX(id) as max_id from wh_month_score GROUP BY pid'))
            ->pluck('max_id')->toArray();
        if(!empty($max_id)){
            $scorelist = MonthScore::whereIn('id',$max_id)
                ->select('pid','x_score','addtime','month','year')
                ->get()
                ->toArray();
            //echo M()->_sql();exit;
            $save['m_score'] = 0;
            $keshi_role = array(3, 4, 5, 6, 7, 8); //科室
            $fumishu_role = array(9, 10, 11, 12, 13, 14); //副秘书长
            $fg_role = array(16, 17, 18, 19, 20, 21); //分管副市长
            foreach ($scorelist as $k_p => $p) {
                //取人工打分对应项目分值
                $a_socre = \App\Models\ArtificialScore::whereRaw("pid={$p['pid']} and n_month={$p['month']} and s_year={$p['year']} ")
                    ->select('pid','s_month','s_year','score','group_id','status')
                    ->get();
                if ($a_socre) {
//                    $v_score= 0;
                    $v_total1 = 0;
                    $v_total2 = 0;
                    $item1 = array();
                    $item2 = array();
                    foreach ($a_socre as $k1 => $v1) {
                        if ($v1['status'] == '1') {
                            $item1[] = $v1; //3个特殊角色数组
                        } else {
                            $item2[] = $v1;
                        }
                    }
//                    dump($item1);
//                    dump($item2);
                    if (count($item1) == 3) {
                        //3个角色都打分 才计算分数
                        foreach ($item1 as $ke => $va) {
                            if (in_array($va['group_id'], $keshi_role)) {
                                $v_score = $va['score'] * 0.2;
                                //echo '1'.'<br/>';
                            }
                            if (in_array($va['group_id'], $fumishu_role)) {
                                $v_score = $va['score'] * 0.3;
                                //echo '2'.'<br/>';
                            }
                            if (in_array($va['group_id'], $fg_role)) {
                                $v_score = $va['score'] * 0.5;
                                //echo '3'.'<br/>';
                            }
                            $v_total1 += $v_score;
                        }
                    }
                    //dump($v_total);exit;
                    if (is_array($item2)) {
                        foreach ($item2 as $k2 => $v2) {
                            $v_total2 += $v2['score'];
                        }
                    }
                    $t_socre = $v_total1 + $v_total2; //3个特殊账号 + 非特殊账号
                    $save['m_score'] = $p['x_score'] + $t_socre;
                } else {
                    $save['m_score'] = $p['x_score'];
                }

                if (intval($save['m_score']) < 0) {
                    $save['m_score'] = '0';
                } elseif (intval($save['m_score']) >= 100) {
                    $save['m_score'] = '100';
                }
//                echo 'pid='.$p['pid'].'<br/>';
//                dump($save['m_score']);
                \App\Models\Project::where('id',$p['pid'])->update($save); // 根据条件更新记录
            }
        }
        echo 'END:' . date('Y-m-d H:i:s', time()) . '<br/>';
    }

    //调试log
    protected function test_log($data){
        file_put_contents('test.log', $data . PHP_EOL, FILE_APPEND);
    }
}
