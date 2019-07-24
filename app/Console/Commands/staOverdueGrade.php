<?php

namespace App\Console\Commands;

use App\Models\Option;
use App\Models\Progress;
use App\Models\Project;
use Illuminate\Console\Command;

class staOverdueGrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sta:overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '项目逾期';

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
        $now_y = date('Y', time()); //当前年份
        $now_time = time();
        $env_wad_day = (int)Option::where('key','wad_day')->value('value');//补填天数配置
        $id = request()->input('id') ?? '';//项目id
        //时间范围判断
//        $day_arr = getthemonth(date('Y-m-d', time()));
//        $last_day = $day_arr[1]; //定时任务 当月最后一天
//        $flag = $day_arr[2]; //是否在时间范围内
        //基础条件 在建项目  排除 4：项目完结 5：调整项目 6：未完结项目
        $where_project = Project::getjszProject();
        //针对单个项目计算逾期
        if ($id != '') {
            $where_pid = ['id', '=', $id];
            array_push($where_project, $where_pid);
        }
        // 分块处理
        $sqls=[];
        \App\Models\Project::where($where_project)
            ->select('id', 'uid', 'fen_uid','uptime')->chunk(100, function ($projectlist) use ($now_time,&$sqls,$env_wad_day) {
                foreach ($projectlist as $k => $v) {
                    //echo $v['id'] . '<br/>';
                    $uptime = date('Y-m-d', $v['uptime']); //项目入库时间
                    $uptime_m = date('m', $v['uptime']);//入库月份
                    $wadtime = date("Y-m-d",strtotime("$uptime +$env_wad_day day"));   //补填时间
                    $wadtime_m = date('m', strtotime($wadtime)); //补填月份

                    //在冻结项目范围内 跳出循环
                    if (in_array($v['id'], $this->_pid_array)) {
                        continue;
                    }
                    $customlist = \App\Models\ProjectPlanCustom::where('pid', $v['id'])->orderBy('id', 'asc')->select('id', 'pid', 'p_year', 'p_month')->get()->toArray(); //查询项目自定义表 根据此表 进行循环判断
                    $month_list=[];
                    foreach ($customlist as $k_s => $v_s) {
                        $month_list = explode(',', $v_s['p_month']);
                        //循环查找 进度表中当月是否有记录
                        foreach ($month_list as $k_m => $v_m) {
                            if (!empty($v_m)) {
                                $progress = Progress::whereRaw('custom_id =' . $v_s['id'] . ' and pid = ' . $v_s['pid'] . ' and `month`=' . $v_m . ' and p_year=' . $v_s['p_year'] . '')
                                    ->select('id', 'pid', 'custom_id', 'month', 'p_status')
                                    ->orderByRaw('p_time desc')
                                    ->first();
                                /**新增入库后项目逾期处理方式 start **/
                                $k_month = getMonthNum($wadtime,$uptime);//补填时间 跟入库时间 差值是否垮月
                                $time_now = date('Y-m-d', time());  // 当前时间
                                if(($time_now <=$wadtime) && ($k_month ==0) ){
                                    //当前时间还在填报时间内的 并且没有跨月的
                                    if($v_m < $uptime_m){
                                        //小于入库月份的 不计算逾期
                                        continue;
                                    }
                                }elseif(($wadtime > $time_now) && ($k_month ==0)){
                                    //当前时间不在填报时间内的 并且没有跨月的
                                    if(($v_m < $uptime_m) && ($progress['p_status'] == 4) ){
                                        //小于入库月份的 补填为100%的进度 不计算逾期
                                        continue;
                                    }
                                }elseif(($time_now <=$wadtime) && ($k_month >0) ){
                                    //当前时间还在填报时间内的 并且跨月的
                                    if($v_m < $wadtime_m){
                                        //小于补填月份的 不计算逾期
                                        continue;
                                    }
                                }elseif(($wadtime > $time_now) && ($k_month >0)){
                                    //当前时间不在填报时间内的 并且跨月的
                                    if(($v_m < $wadtime_m) && ($progress['p_status'] == 4) ){
                                        //小于补填月份的 补填为100%的进度 不计算逾期
                                        continue;
                                    }
                                }
                                /**新增入库后项目逾期处理方式 end **/
                                $datetime_s = date_create(date('Y-m-d', time()));  // 当前时间
                                $now_m = $v_s['p_year'] . '-' . $v_m;
                                $today = date($now_m);
                                $last_day_arr = getthemonth($today);
                                $datetime_last = date_create($last_day_arr[1]);  //当月最后一天
                                $interval = date_diff($datetime_s, $datetime_last);
                                $y_time = $interval->format('%R%a');
//                                  echo 'y_time:'.$y_time.'<br/>';
//                                  echo '<br/>';
                                if ($y_time < 0 && $y_time > -30) {
                                    //逾期 小于 30天
                                    $data['status'] = 3;
                                } elseif ($y_time <= -30 && $y_time > -60) {
                                    //进展缓慢
                                    $data['status'] = 1;
                                } elseif ($y_time <= -60) {
                                    //严重滞后
                                    $data['status'] = 2;
                                }
                                $data['y_time'] = $y_time;
                                // echo 'custom_id:'.$v_s['id'].'<br/>';
                                // echo '月份:'.$v_m.'<br/>';
                                if (!$progress) {
                                    //系统扫描未填 插入 m_progress 表
                                    if ($data['y_time'] < 0) {
                                        //逾期时 才记录
                                        $remark = '系统统计';
                                        $sqls[] = "({$v_s['pid']},{$now_time},{$data['y_time']},{$v_s['id']},{$v_m},{$v_s['p_year']},'{$remark}',5)";

                                    }
                                } else {
                                    // echo 'update_id_day:'.$v_s['pid'].'<br/>';
                                    //更新操作
                                    $progress_update = Progress::whereRaw('custom_id =' . $v_s['id'] . ' and pid = ' . $v_s['pid'] . ' and month=' . $v_m . ' and p_year=' . $v_s['p_year'] . ' and p_status =5 ')
                                        ->select('id', 'pid', 'custom_id', 'month')
                                        ->first();
                                    if ($progress_update) {
                                        //echo '更新id_day:' . $progress_update['id'] . '<br/>';
                                        $data_update['p_time'] = $now_time;
                                        if ($data['y_time'] < 0) {
                                            $data_update['y_time'] = $y_time;
                                        }
                                        //已完成不扫描
                                        if ($progress['p_status'] != 4) {
                                            Progress::whereRaw('id=' . $progress_update['id'] . ' ')->update($data_update); // 根据条件更新记录
                                        }
                                    } else {
                                        if ($data['y_time'] < 0) {
                                            //已完成不扫描
                                            if ($progress['p_status'] != 4) {
                                                //逾期时 才记录
                                                $remark = '系统统计';
                                                $sqls[] = "({$v_s['pid']},{$now_time},{$data['y_time']},{$v_s['id']},{$v_m},{$v_s['p_year']},'{$remark}',5)";
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            });
        //$this->test_log($aa);
        if (!empty($sqls)) {
            \DB::insert('insert into `project_progress`(pid,p_time,y_time,custom_id,`month`,p_year,remark,p_status) values ' . implode(',', $sqls));
        }

        //数据添加完毕后 按纵轴判断取最大逾期天数 更新主表状态 排除项目调整 项目完结 项目未完成
        $progress_where =Project::getjszProject(1);
        //模型有软删除 需要用别名的用法
        (new Progress())
            ->setTable('g')
            ->from('project_progress as g')
            ->where($progress_where)
            ->leftJoin('project as p', 'p.id', '=', 'g.pid')
            ->select('g.id','g.pid','g.custom_id','g.month')
            ->orderBy('g.id','asc')
            ->chunk(100, function ($id_list) {
                //dd($id_list);
                $ids = null;
                $no_sql = '';
                foreach ($id_list as $k_id => $v_id) {
                    $no_sql = " pid={$v_id['pid']} and custom_id = {$v_id['custom_id']} and month = {$v_id['month']}";
                    $ids_item = Progress::whereRaw($no_sql)->select('id','pid','custom_id')->get();
                    foreach ($ids_item as $k_item => $v_item) {
                        //$ids .= $v_item['id'] . ','; //节点对应月份相关所有进度id  为更新主表状态 排除这些id 不排除 会有问题
                        $ids[] = $v_item['id'];
                    }
                }
                $progress_zz = Progress::where('p_status','!=',4)
                    ->when($ids, function ($query) use ($ids) {
                        // 为更新主表状态 排除这些id 不排除 会有问题
                        return $query->whereNotIn('id', $ids);// $ids 有值时 执行
                    })
                    ->select('id','pid','custom_id','month','y_time','p_year')
                    ->orderBy('pid','asc')
                    ->get();
                $progress_pid = array();
                $progress_card = array();
                foreach ($progress_zz as $k_zz => $v_zz) {
                    $progress_pid[$v_zz['pid']][]['y_time'] = $v_zz['y_time'];
                    $progress_card[$v_zz['pid']][] = $v_zz;
                }
                //定时更新主表状态
                foreach ($progress_pid as $k_pid => $v_item) {
                    $count_v = count($v_item);
                    if ($count_v == 1) {
                        $min_y_time = $v_item[0]['y_time'];
                        if ($min_y_time < 0 && $min_y_time > -30) {
                            $save['pro_status'] = 3;
                        } elseif ($min_y_time <= -30 && $min_y_time > -60) {
                            $save['pro_status'] = 1;
                        } elseif ($min_y_time <= -60) {
                            $save['pro_status'] = 2;
                        } else {
                            $save['pro_status'] = 0;
                        }
                        //echo 'y_time='.$min_y_time;
                        Project::where('id',$k_pid)->update($save); // 更新主表状态
                    } elseif ($count_v > 1) {
                        $min_list = min($v_item); //取最大 逾期天数  逾期为负数 所以用 min()
                        $min_y_time = $min_list['y_time'];
                        //echo 'y_time='.$min_y_time.'<br/>';
                        if ($min_y_time < 0 && $min_y_time > -30) {
                            $save['pro_status'] = 3;
                        } elseif ($min_y_time <= -30 && $min_y_time > -60) {
                            $save['pro_status'] = 1;
                        } elseif ($min_y_time <= -60) {
                            $save['pro_status'] = 2;
                        } else {
                            $save['pro_status'] = 0;
                        }
                        Project::where('id',$k_pid)->update($save);; // 更新主表状态
                    }
                }
                //dump($progress_card);
                //插入发牌记录
                foreach ($progress_card as $k_card => $v_card) {
                    foreach ($v_card as $k_c => $v_c) {
                        $ytime = $v_c['y_time'];
                        $pid = $v_c['pid'];
                        $progress_id = $v_c['id'];
                        $fuid = 0;
                        if ($ytime <= -30 && $ytime > -60) {
                            $color = 1;
                        } elseif ($ytime <= -60) {
                            $color = 2;
                        }
                        $addtime = time();
                        $p_year = $v_c['p_year'];
                        $custom_id = $v_c['custom_id'];
                        if (($ytime >= 0) || ($ytime < 0 && $ytime > -30)) {
                            continue; //不在范围内 不发牌
                        }
                        $sqls_card[] = "($pid,{$progress_id},{$fuid},{$color},{$addtime},{$p_year},{$custom_id},{$ytime})";
                    }
                }
                if (!empty($sqls_card)) {
                    //插入发牌记录
                    \DB::insert('insert into `card`(pid,progress_id,fuid,color,addtime,p_year,custom_id,y_time) values ' . implode(',', $sqls_card));
                }
            });//分块处理


        echo 'END:' . date('Y-m-d H:i:s', time()) . '<br/>';
    }

    //调试log
    protected function test_log($data){
        file_put_contents('test.log', $data . PHP_EOL, FILE_APPEND);
    }
}
