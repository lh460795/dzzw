<?php

namespace App\Service;

use App\Models\Progress;
use App\Models\Project;
use App\Models\ProjectPlanCustom;
use Illuminate\Support\Facades\DB;
use Auth;

/**
 * Class ScoreService
 * @package App\Service
 * 项目评分类
 */
class ScoreService
{
    private $_pid_array;

    public function __construct()
    {
        $this->_pid_array = [Project::DJ_1, Project::DJ_2, Project::DJ_3]; //排除冻结项目 780   1027    184
    }

    //计算项目得分
    public function computeScore($pid='')
    {
        $now_m = date('n', time()); //当前月份
        //$now_m = 1;
        $now_y = date('Y', time()); //当前年份
        $now_time = time();
        //echo $now_m.'<br/>';
        $now = date('Y-m-d', time()); //当前时间
        //$now = "2018-06-01";
        $month_sql = 0; //插入评分表用
        $month_i='';
//        if ($now_m == 12) {
//            $month = '1,2,3,4,5,6,7,8,9,10,11';
//            $year = $now_y;
//            $month_sql = 12;
//            $map_month = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11);
//        }
        if ($now_m == 1) {
            $month = '1';
            $year = $now_y;
            $month_sql = 1;
            $map_month = [1]; //
        } elseif (($now_m !=1) && ($now_m <=12)) {
            $year = $now_y;
            $month_sql = $now_m;
            for($i=1;$i<$now_m;$i++){
                $month_i .= (int)$i.',';
            }
            $month = rtrim($month_i,',');
            $map_month = collect(explode(',',$month))
                ->map(function ($item, $key) {
                     return (int)$item; //字符串转数组
                })->toArray();
        }
        //dump($month);
        //dump($map_month);
        //基础条件 在建项目  排除 4：项目完结 5：调整项目 6：未完结项目
        if($pid ==''){
            $where_project = \App\Models\Project::getjszProject();
        }else{
            $where_project =[
                ['id','=',$pid]
            ];
        }
        //dd($where_project);
        $sqls = [];
        $html=''; //页面输出
        //分块处理
        \App\Models\Project::where($where_project)
            ->select('id', 'type', 'uid', 'is_year', 'year','pname')
            ->chunk(100, function ($projectlist) use ($now_time, &$sqls, $month,$now_m, $year, $month_sql, $map_month,$now_y,&$html) {
                //dd($projectlist);
                foreach ($projectlist as $p => $project) {
                    $p_type = $project['type']; //项目类型
                    $is_year = $project['is_year']; //是否跨年
                    $pid = $project['id'];//项目id
                    $html.='项目ID : '.$pid.'<br/>';
//                    $html.='项目名称 : '.$project['pname'].'<br/>';
                    //在冻结项目范围内 跳出循环
                    if (in_array($pid, $this->_pid_array)) {
                        continue;
                    }
                    $resinfo = array();
                    $result = array();
                    if (($p_type == '1' && $is_year == '0') || ($p_type == '2' && $is_year == '0')) {
                        //echo 'pid='.$pid.'<br/>';
                        //政府投资 招商引资 且非跨年项目 过滤 没有计划节点
                        $result = ProjectPlanCustom::from('project_plan_custom as p')
                            ->select('p.id','o.uid','p.pid','p.m_value','o.type','p.p_month','t.s_value','t.day','t.day_score','o.is_year')
                            ->leftJoin('wh_score_templates as t','t.s_name','=','p.m_value')
                            ->leftJoin('project as o','o.id','=','p.pid')
                            ->whereRaw("p.pid={$project['id']} and t.type={$project['type']} and p.p_year ={$year} and p.p_month !=''")
                            ->orderBy('p.id')
                            ->get()->toArray();
                        $html.='评分规则：'.'标准表评分规则'.'<br/><br/>';
                        //dd($result);
                    } else {
                        //echo 'pid2='.$pid.'<br/>';
                        $result = ProjectPlanCustom::from('project_plan_custom as p')
                            ->select('p.id','o.uid','p.pid','p.m_value','o.type','p.p_month','o.is_year')
                            ->leftJoin('project as o','o.id','=','p.pid')
                            ->whereRaw("p.pid={$project['id']} and p.p_year ={$year} and p.p_month !=''")
                            ->orderBy('p.id')
                            ->get()->toArray();
                        //echo M()->_sql();
                        //dd($result);
                        $res_count = count($result); //计算项目节点个数
                        $str = '';
                        foreach ($result as $k_d => $v_d) {
                            $str .= $v_d['p_month'];
                        }
                        $str = trim($str, ',');
                        //echo 'str='.$str.'<br/>';
                        $p_month_count = count(explode(',', $str)); //项目√总数
                        //echo 'p_month_count='.$p_month_count.'<br/>';
                        if (($p_type == '1' && $is_year == '1') || ($p_type == '2' && $is_year == '1')) {
                            //政府投资 招商引资 跨年项目
                            $s_value = 100 / $res_count; //计算单个节点默认分
                        } else {
                            //其他类 深化改革、社会事业类项目 95分 任务举措 5分工作成效
                            $s_value = 95 / $res_count;
                        }
                        $month_list1 = explode(',', $month);
                        $no_project1 = '';
                        foreach ($month_list1 as $o => $itom1) {
                            $no_project1 .= "FIND_IN_SET($itom1,p_month) or ";
                        }
                        $no_project1 = trim($no_project1, 'or ');
                        $pro_info = ProjectPlanCustom::whereRaw("pid={$project['id']} and ({$no_project1}) and p_year ={$year}")
                            ->select('id','pid','p_month')
                            ->get()->toArray();
                        //echo M()->_sql();
                        $g_count = 0;  //计算当前月份 √数量
                        if ($now_m != 1) { //此判断很重要
                            foreach ($pro_info as $ke => $ve) {
                                $p_month = explode(',', $ve['p_month']);
                                $g_count += count(array_intersect($p_month, $map_month));
                            }
                        }
                        //查询进度表大于当前月份 如果有填写进度 √数量 累加 $now_m  1月份 不用判断
                        $m_progress = Progress::whereRaw("pid = {$pid} and month >={$now_m} and p_year={$year} and p_status=4")
                            ->select('custom_id','month')
                            ->groupBy('custom_id','month')
                            ->get()
                            ->toArray();
                        if (!empty($m_progress)) {
                            $g_count += count($m_progress);
                        }

                        //新增默认分值下标
                        foreach ($result as $k1 => $v1) {
                            $result[$k1]['s_value'] = $s_value;
                            $result[$k1]['p_month_count'] = $g_count; //新规则 总勾数跟当前勾数量一致  原来取的是：$p_month_count
                            $result[$k1]['g_count'] = $g_count; //当前月份 √数量 计划总分有用
                        }
                        $html.='评分规则：'.'自定义表评分规则'.'<br/><br/>';
                        //dump($pid).'<br/>';
                    }
                    //echo 'pid='.$pid.'<br/>';
                    //dd($result);

                    foreach ($result as $key => $value) {
                        $resinfo[$value['pid']][] = $value;
                    }
                    //dump($resinfo);

                    $default_value = 0; //  项目加点无进度 默认总分
                    foreach ($resinfo as $k => $item) {
                        $progresslist = array();
                        foreach ($item as $i => $v) {
                            if ($v['p_month'] == '') {
                                //echo 's_value='.$v['s_value'].'<br/>';
                                //项目 节点没有√ 默认总分
                                $default_value += $v['s_value'];
                            } else {
                                //查询进度表大于当前月份 如果有填写进度 ->group("custom_id")
                                $m_progress = Progress::whereRaw("pid = {$v['pid']} and month >={$now_m} and p_year={$year} and custom_id={$v['id']} and p_status=4")
                                    ->select('month')
                                    ->get()->toArray();
                                //dump($m_progress).'<br/>';
                                $month_add = '';
                                //dump($m_progress);
                                if (!empty($m_progress)) {
                                    //组合有填写进度的月份 并追加给 $month
                                    foreach ($m_progress as $k_m => $v_mp) {
                                        $month_add .= ',' . $v_mp['month'];
                                    }
                                    //echo 'month_add='.$month_add.'<br/>';
                                }
                                if ($month_add != '') {
                                    $month_new = $month . $month_add;
                                } else {
                                    $month_new = $month;
                                }
                                if ($now_m != 1) {//此判断很重要
                                    $progress_where = '';
                                } else {
                                    $progress_where = 'and p_status=4';
                                }
                                $progresslist = Progress::whereRaw("pid={$v['pid']} and custom_id={$v['id']} and month in ({$month_new}) and p_year ={$year} {$progress_where}")
                                    //->select('id','pid','custom_id','month','p_year','y_time','p_status','FROM_UNIXTIME(p_time) as p_time')
                                    ->select(DB::raw('id,pid,custom_id,month,p_year,y_time,p_status,FROM_UNIXTIME(p_time) as p_time'))
                                    ->orderBy('p_time','desc')
                                    ->get()->toArray();

                                if ($progresslist) {
                                    $plist = array();
                                    foreach ($progresslist as $p => $pro) {
                                        $plist[$pro['month']][] = $pro; //将月份作为数组下标
                                    }
                                    $resinfo[$k][$i]['progress'] = $plist;
                                }
                                //dd($resinfo);
                                //echo 'g_count='.$g_count.'<br/>';
                            }
                        }
                    }

                    //dump($resinfo);
                    //计算当前月份 基础分 逾期基础分
                    foreach ($resinfo as $k_j => $item_j) {
                        $j_count = 0;
                        foreach ($item_j as $i_j => $v_item_j) {
                            $kf = 0; //逾期扣分
                            if (isset($v_item_j['progress'])) {
                                $j_count = count($v_item_j['progress']);
                                //echo 'g_count='.$j_count.'<br/>';
                                foreach ($v_item_j['progress'] as $p_j => $v_j) {
                                    //标准表 跟自定义表 单√计算方法不同
                                    //$g_count = count(explode(',', trim($v['p_month'], ',')));
                                    if (($v_item_j['type'] == '1' && $v_item_j['is_year'] == '1') || ($v_item_j['type'] == '2' && $v_item_j['is_year'] == '1')) {
                                        $jcf = 100 / $v_item_j['p_month_count'];
                                        //echo '1d'.'<br/>';
                                    } elseif (($v_item_j['type'] == '1' && $v_item_j['is_year'] == '0') || ($v_item_j['type'] == '2' && $v_item_j['is_year'] == '0')) {
                                        $jcf = $v_item_j['s_value'] / $j_count; //单√基础分
                                        //echo '2d'.'<br/>';
                                    } elseif (($v_item_j['type'] == '3') || ($v_item_j['type'] == '4')) {
                                        $jcf = 95 / $v_item_j['p_month_count'];
                                        //echo '3d'.'<br/>';
                                    }
                                    $resinfo[$k_j][$i_j]['jcf'] = $jcf;
                                    //逾期分数
                                    //if ($v_item_j['type'] == '1') {
                                    if (($v_item_j['type'] == '1' && $v_item_j['is_year'] == '0')) {
                                        if (trim($v_item_j['m_value']) == '项目控制') {
                                            //每超出一项扣5分，扣完为止
                                            $kf = 5;
                                        } else {
                                            $kf = $v_item_j['day_score']; //扣分值 取数据库的值
                                        }
                                    } elseif (($v_item_j['type'] == '2' && $v_item_j['is_year'] == '0')) {
                                        $kf = $v_item_j['day_score']; //扣分值 取数据库的值
                                    } elseif (($v_item_j['type'] == '1' && $v_item_j['is_year'] == '1') || (($v_item_j['type'] == '2' && $v_item_j['is_year'] == '1'))) {
                                        //每推后10天扣2分，扣完为止。
                                        $kf = 0.2; //扣分值
                                    } else {
                                        //每推后10天扣1分，扣完为止。
                                        $kf = 0.1; //扣分值
                                    }
                                    // 计算 逾期基础分
//                                echo 'kf='.$kf.'<br/>';
//                                echo '2_j_count='.$j_count.'<br/>';
                                    $yq_jcf = $kf / $j_count;
                                    $resinfo[$k_j][$i_j]['yq_jcf'] = $yq_jcf;
                                }
                            }
                        }
                    }
                    //dump($resinfo);
                    //$this->test_log(var_export($resinfo,true));
                    $p_width = 0; //百分比
                    $sj_score = 0; //实际分值
                    $kf_count = 0; //扣分值
                    $total = 0; //当前月评分
                    $jh_score = 0; //当前计划分值
                    $yq_score = 0; //逾期总扣分
                    foreach ($resinfo as $k_n => $item_n) {
                        foreach ($item_n as $i_n => $v_item) {
                            if (isset($v_item['progress'])) {
                                foreach ($v_item['progress'] as $p => $v_p) {
                                    //dump($v_p);
                                    //$m 月份下标
                                    //echo 'count='.count($v_p).'<br/>';
                                    $v_count = count($v_p);
                                    foreach ($v_p as $m => $v_m) {
                                        $m_key = $m + 1;
                                        //echo 'm_key='.$m.'<br/>';
                                        //取数组第一条数据 $v_count == $m_key
                                        if ($m == 0) {
                                            if (($v_count == 1) && ($v_m['p_status'] == '5')) {
                                                //continue; //没有填写进度 排除系统扫描
                                            }
                                            if (isset($v_p[$m_key]['p_status']) && ($v_m['p_status'] == '5') ) {
                                                $p_status = $v_p[$m_key]['p_status']; //多条数据，且最新一条是系统扫描时，取第二个元素的p_status
                                                //echo 'p_status='.$p_status;
                                                if ($p_status == '0') {
                                                    $p_width = 0;
                                                } elseif ($p_status == '1') {
                                                    $p_width = 0.25;
                                                } elseif ($p_status == '2') {
                                                    $p_width = 0.5;
                                                } elseif ($p_status == '3') {
                                                    $p_width = 0.75;
                                                } elseif ($p_status == '4') {
                                                    $p_width = 1;
                                                } else {
                                                    $p_width = 0; //没有填写进度 只有系统扫描数据情况
                                                }
                                                $day = str_replace('-', '', $v_m['y_time']);
                                                //echo 'day='.$day;
                                                if ($v_m['y_time'] >= 0) {
                                                    //计算 实际分值
                                                    $sj_score += $v_item['jcf'] * $p_width;
                                                    $shiji_score = $v_item['jcf'] * $p_width;

                                                    $html.= "实际分值公式：（节点基础分 * 进度百分比）" . "<br/>";
                                                    $html.= "实际分值计算：（{$v_item['jcf']} * {$p_width}）= {$shiji_score} " . "<br/>";
                                                    $html.= "项目节点：{$v_item['m_value']} " . "<br/>";
                                                    $html.= "月份：{$v_m['month']} " . "<br/>";
                                                    $html.= "<br/>";
                                                } else {
                                                    if (($v_item['type'] == '1' && $v_item['is_year'] == '0')) {
                                                        if (trim($v_item['m_value']) == '项目控制') {
                                                            //每超出一项扣5分，扣完为止。
                                                            $kf_count = 0;
                                                        } else {
                                                            $kf_count = intval($day / $v_item['day']); //扣分次数
                                                        }
                                                    } elseif (($v_item['type'] == '2' && $v_item['is_year'] == '0')) {
                                                        $kf_count = intval($day / $v_item['day']); //扣分次数
                                                    } elseif (($v_item['type'] == '1' && $v_item['is_year'] == '1') || (($v_item['type'] == '2' && $v_item['is_year'] == '1'))) {
                                                        //每推后10天扣2分，扣完为止。
                                                        $kf_count = intval($day / 1); //扣分次数
                                                    } else {
                                                        //每推后10天扣1分，扣完为止。
                                                        $kf_count = intval($day / 1); //扣分次数
                                                    }

                                                    //计算 实际分值
                                                    $yuqi_kf = $v_item['yq_jcf'] * $kf_count;
                                                    if ($yuqi_kf > $v_item['jcf']) {
                                                        //如果逾期扣分大于基础分 扣分值跟基础分值相同
                                                        $shiji_score = ($v_item['jcf'] * $p_width) - ($v_item['jcf']);
                                                    } else {
                                                        $shiji_score = ($v_item['jcf'] * $p_width) - ($v_item['yq_jcf'] * $kf_count);
                                                    }
                                                    //echo '1_shiji_score=' . $shiji_score . '<br/>';
                                                    //实际分值不能为负数
                                                    if ($shiji_score < 0) {
                                                        $shiji_score = 0;
                                                    }
                                                    $sj_score += $shiji_score;
                                                    //逾期总扣分
                                                    if ($yuqi_kf > $v_item['jcf']) {
                                                        $yq_score += $v_item['jcf'];
                                                    } else {
                                                        $yq_score += $yuqi_kf;
                                                    }
//                                                echo 'jiedian=' . $v_item['m_value'] . '<br/>';
//                                                echo '666=' . $sj_score . '<br/>';
                                                    //逾期扣分
                                                    if($yuqi_kf > $v_item['jcf']){
                                                        $yq_kf = $v_item['jcf'];
                                                    }else{
                                                        $yq_kf = $v_item['yq_jcf'] * $kf_count;
                                                    }
                                                    $html.= "实际分值公式：（节点基础分 * 进度百分比）- （逾期基础分 * 扣分值）" . "<br/>";
                                                    if($yuqi_kf > $v_item['jcf']){
                                                        $html.= "实际分值计算：（{$v_item['jcf']} * {$p_width}）- （{$v_item['jcf']}）= {$shiji_score} " . "<br/>";
                                                    }else{
                                                        $html.= "实际分值计算：（{$v_item['jcf']} * {$p_width}）- （{$v_item['yq_jcf']} * {$kf_count}）= {$shiji_score} " . "<br/>";
                                                    }
                                                    if($yuqi_kf > $v_item['jcf']){
                                                        $html.= "逾期扣分计算： {$yq_kf} " . "<br/>";
                                                    }else{
                                                        $html.= "逾期扣分计算：（{$v_item['yq_jcf']} * {$kf_count}）= {$yq_kf} " . "<br/>";
                                                    }

                                                    $html.= "项目节点：{$v_item['m_value']} " . "<br/>";
                                                    $html.= "月份：{$v_m['month']} " . "<br/>";
                                                    $html.= "<br/>";
                                                }
                                            } else {
                                                //echo 333345;
                                                $p_status = $v_m['p_status'];
                                                //echo 'p_status2='.$p_status.'<br/>';
                                                if ($p_status == '0') {
                                                    $p_width = 0;
                                                } elseif ($p_status == '1') {
                                                    $p_width = 0.25;
                                                } elseif ($p_status == '2') {
                                                    $p_width = 0.5;
                                                } elseif ($p_status == '3') {
                                                    $p_width = 0.75;
                                                } elseif ($p_status == '4') {
                                                    $p_width = 1;
                                                }
                                                $day = str_replace('-', '', $v_m['y_time']);
                                                if ($v_m['y_time'] >= 0) {
                                                    //计算 实际分值 判断 提前完成月份 只要填写了进度 只取节点基础分
                                                    if (($v_m['p_year'] == $now_y) && (intval($v_m['month']) >= intval($now_m))) {
                                                        //不用乘以百分比 直接取节点基础分
                                                        $sj_score += $v_item['jcf'];
                                                        $shijie_score = $v_item['jcf'];

                                                        $html.= "实际分值公式：（节点基础分）（提前完成）" . "<br/>";
                                                        $html.= "实际分值计算：（{$v_item['jcf']}）= {$shijie_score} " . "<br/>";
                                                        $html.= "项目节点：{$v_item['m_value']} " . "<br/>";
                                                        $html.= "月份：{$v_m['month']} " . "<br/>";
                                                        $html.= "<br/>";
                                                    } else {
                                                        $sj_score += $v_item['jcf'] * $p_width;
                                                        $shijie_score = $v_item['jcf'] * $p_width;

                                                        $html.= "实际分值公式：（节点基础分 * 进度百分比）" . "<br/>";
                                                        $html.= "实际分值计算：（{$v_item['jcf']} * {$p_width}）= {$shijie_score} " . "<br/>";
                                                        $html.= "项目节点：{$v_item['m_value']} " . "<br/>";
                                                        $html.= "月份：{$v_m['month']} " . "<br/>";
                                                        $html.= "<br/>";
                                                    }
//                                                echo 'jiedian=' . $v_item['m_value'] . '<br/>';
//                                                echo '777=' . $sj_score . '<br/>';
                                                } else {
                                                    if (($v_item['type'] == '1' && $v_item['is_year'] == '0')) {
                                                        if (trim($v_item['m_value']) == '项目控制') {
                                                            //每超出一项扣5分，扣完为止。
                                                            $kf_count = 0;
                                                        } else {
                                                            $kf_count = intval($day / $v_item['day']); //扣分次数
                                                        }
                                                    } elseif (($v_item['type'] == '2' && $v_item['is_year'] == '0')) {
                                                        $kf_count = intval($day / $v_item['day']); //扣分次数
                                                    } elseif (($v_item['type'] == '1' && $v_item['is_year'] == '1') || (($v_item['type'] == '2' && $v_item['is_year'] == '1'))) {
                                                        //每推后10天扣2分，扣完为止。
                                                        $kf_count = intval($day / 1); //扣分次数
                                                    } else {
                                                        //每推后10天扣1分，扣完为止。
                                                        $kf_count = intval($day / 1); //扣分次数
                                                    }

                                                    //计算 实际分值
                                                    $yuqi_kf = $v_item['yq_jcf'] * $kf_count;
                                                    if ($yuqi_kf > $v_item['jcf']) {
                                                        //如果逾期扣分大于基础分 扣分值跟基础分值相同
                                                        $shiji_score = ($v_item['jcf'] * $p_width) - ($v_item['jcf']);
                                                    } else {
                                                        $shiji_score = ($v_item['jcf'] * $p_width) - ($v_item['yq_jcf'] * $kf_count);
                                                    }
                                                    //echo '2_shiji_score=' . $shiji_score . '<br/>';
                                                    //实际分值不能为负数
                                                    if ($shiji_score < 0) {
                                                        $shiji_score = 0;
                                                    }
                                                    $sj_score += $shiji_score;

                                                    //逾期扣分
                                                    if($yuqi_kf > $v_item['jcf']){
                                                        $yq_kf = $v_item['jcf'];
                                                    }else{
                                                        $yq_kf = $v_item['yq_jcf'] * $kf_count;
                                                    }
                                                    $html.= "实际分值公式：（节点基础分 * 进度百分比）- （逾期基础分 * 扣分值）" . "<br/>";
                                                    if($yuqi_kf > $v_item['jcf']){
                                                        $html.= "实际分值计算：（{$v_item['jcf']} * {$p_width}）- （{$v_item['jcf']}）= {$shiji_score} " . "<br/>";
                                                    }else{
                                                        $html.= "实际分值计算：（{$v_item['jcf']} * {$p_width}）- （{$v_item['yq_jcf']} * {$kf_count}）= {$shiji_score} " . "<br/>";
                                                    }
                                                    if($yuqi_kf > $v_item['jcf']){
                                                        $html.= "逾期扣分计算： {$yq_kf} " . "<br/>";
                                                    }else{
                                                        $html.= "逾期扣分计算：（{$v_item['yq_jcf']} * {$kf_count}）= {$yq_kf} " . "<br/>";
                                                    }

                                                    $html.= "项目节点：{$v_item['m_value']} " . "<br/>";
                                                    $html.= "月份：{$v_m['month']} " . "<br/>";
                                                    $html.= "<br/>";
                                                    //echo '888=' . $sj_score . '<br/>';
                                                    //逾期总扣分
                                                    if ($yuqi_kf > $v_item['jcf']) {
                                                        $yq_score += $v_item['jcf'];
                                                    } else {
                                                        $yq_score += $yuqi_kf;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                //计算 计划总分
                                $jcf = $v_item['jcf'];
                                //echo 'v_count=' . $v_count . '<br/>';
                                //计算√数量
                                $gg_count = count($v_item['progress']);
                                $dg_count = $v_item['g_count']; //自定义项目 √数量
                                if (($v_item['type'] == '1' && $v_item['is_year'] == '0') || ($v_item['type'] == '2' && $v_item['is_year'] == '0')) {
                                    //固定模板
                                    if ($gg_count == 1) {
                                        //一个√情况 取节点默认分
                                        $jh_score += $v_item['s_value'];
                                    } else {
                                        //多√ 取单钩基础分 * √数量
                                        $jh_value = $jcf * $gg_count;
                                        $jh_score += $jh_value;
                                    }
                                } else {
                                    $jh_value_zdy = $jcf * $dg_count;
                                    $jh_score = $jh_value_zdy;
                                    //echo '2_jh_score=' . $jh_score . '<br/>';
                                }
                            }
                        }
                    }

                    //查询进度表大于当前月份 如果有填写进度
                    $m_progress = Progress::whereRaw("pid = {$project['id']} and month >={$now_m} and p_year={$year} and p_status=4")
                        ->select('custom_id','month')
                        ->groupBy('custom_id')
                        ->get()
                        ->toArray();
                    $month_add = '';
                    //dd($m_progress);
                    if (!empty($m_progress)) {
                        //组合有填写进度的月份 并追加给 $month
                        foreach ($m_progress as $k_m => $v_mp) {
                            $month_add .= ',' . $v_mp['month'];
                        }
                        //echo 'month_add='.$month_add.'<br/>';
                    }
                    if ($month_add != '') {
                        $month_new_plan = $month . $month_add;
                    } else {
                        $month_new_plan = $month;
                    }
                    $month_new_plan = str_unique($month_new_plan, ','); //字符串去重

                    $month_list = explode(',', $month_new_plan);
                    $no_project = '';
                    foreach ($month_list as $o => $itom) {
                        //排除当前月 plan_custom p_month 没有计划项目id
                        $no_project .= "FIND_IN_SET($itom,p_month) or ";
                    }
                    $no_project = trim($no_project, 'or ');
//                echo 'no_project=' . $no_project . '<br/>';
//                echo 'pid=' . $project['id'] . '<br/>';
//              echo '默认分值=' . $default_value . '<br/>';
//                echo '实际分值=' . $sj_score . '<br/>';
//                echo '计划总分=' . $jh_score . '<br/>';
                    $html.= '当前实际分值=' . $sj_score . '<br/>';
                    $html.= '计划总分=' . $jh_score . '<br/>';
                    $fz = $sj_score;
                    $fm = $jh_score;
//                echo '分子=' . $fz . '<br/>';
//                echo '分母=' . $fm . '<br/>';
                    if ($fz < 0) {
                        $fz = 0; //实际分值+默认分值为负数 默认为0
                    }

                    $total = ($fm > 0) ?round(($fz / $fm) * 100, 2) : 0;
                    if ($total > 100) {
                        $total = '100';
                    }
                    $html.= "<br/>";
                    $html.= "最终得分公式: （当前实际分值/计划总分）" . "<br/>";
                    $html.= "最终得分计算：（{$fz} / {$fm}）* 100 = {$total} 分 (四舍五入取2位小数)" . "<br/>";
                    //插入sql $now_time
                    $proinfo = ProjectPlanCustom::whereRaw("pid={$project['id']} and ({$no_project}) and p_year={$year}")
                        ->select('id','pid')
                        ->first();
                    if (!$proinfo) {
                        //排除当前月 plan_custom p_month 没有计划项目id
                        continue;
                    }
                    $sqls[] = [
                        'pid'=>$project['id'],
                        's_score'=>$sj_score,
                        'x_score'=>$total,
                        'j_score'=>$jh_score,
                        'y_score'=>$yq_score,
                        'd_score'=>$default_value,
                        'addtime'=>$now_time,
                        'month'=>$month_sql,
                        'year'=>$project['year'],
                        'type'=>$project['type']
                    ];
                }
            });
        return ($pid !='')? $html : $sqls;
    }

    //调试log
    protected function test_log($data){
        file_put_contents('test.log', $data . PHP_EOL, FILE_APPEND);
    }

}