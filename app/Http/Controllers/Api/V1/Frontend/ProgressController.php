<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Resources\Api\V1\Frontend\ProgressCollection;
use App\Http\Resources\Api\V1\Frontend\RunLogCollection;
use App\Models\Option;
use App\Models\Project;
use App\Models\Natong;
use App\Models\NatongRecord;
use App\Models\ProjectPlanCustom;
use App\Models\Progress;
use Auth;
use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\ProgressRequest;
use Illuminate\Support\Facades\DB;
use App\Kcb;
use App\Models\CoScore;
use App\Models\Unit;
use App\Models\Upload;

class ProgressController extends Controller
{


    /**
     * @param ProgressRequest $request
     * @param Project $Project
     * 进度提交页面展示
     */
    public function show(ProgressRequest $request)
    {
//        header('access-Control-Allow-Origin:*');
        $user = Auth::guard('api')->user();

        //默认显示
        $now_m = date('n', time()); //当前月份
        $res_val = request()->all();
        $data = json_decode($res_val['data'], true);
        //$now_y = date('Y', time()); //当前年份
        $cid = $data['cid'] ?? '0'; //自定义id
        $month = $data['month'] ?? '0'; //月份

        $flag = 0; //默认可以填写进度
        $xb_flag = 0; //是否显示协办评分按钮
        $upload_flag = 0; //是否显示修改附件按钮
        $edit_flag = 0; //是否显示修改协办打分按钮
        $tq_flag = 0; //是否显示提前完成下拉
        $m_account = 0; //是否显示当月具体投资额
        //取出对应月份
        $months = 'content' . $month;

        $pro_info = ProjectPlanCustom::with(['projectRes' => function ($q) use ($cid) {
            $q->select('id', 'pname', 'type');
        }])
            ->where('id', $cid)
            ->first();

        if (collect($pro_info)->isNotEmpty()) {
            $pro_info = $pro_info->toArray();
            $pro_info['pname'] = $pro_info['project_res']['pname'];
            $pro_info['type'] = $pro_info['project_res']['type'];
        }

        $pid = $pro_info['pid'];
        if (in_array($pid, array(Project::DJ_1, Project::DJ_2, Project::DJ_3))) {
            return $this->failed('此项目已被冻结,不能填写进度！');
        }

        //先判断月份 当前月份小于等于节点月份 且不等于12月份 可提前完成
        if ($now_m <= $month && $month != 12) {
            //判断此节点表
            $p_customs = ProjectPlanCustom::where('id', $cid)->select('p_month')->first();
            if (collect($p_customs)->isNotEmpty()) {
                $p_customs = $p_customs->toArray();
            }
            $p_custom = $p_customs['p_month'];

            if ($p_custom) {
                $arr_plan = explode(',', $p_custom); //过滤掉数组中的空值
                //要提前完成 不能只有1个月份
                if (count($arr_plan) != 1) {
                    if ($month != end($arr_plan)) {
                        //节点月份 不是最后一个月份 可提前完成
                        $tq_flag = 1;
                    }
                }
            }
        }
        //dump($tq_flag);
        $p_year = $pro_info['p_year'];
        $p_progress = intval($month) . "月计划：" . $pro_info['m_value'];

        $pname = $pro_info['pname']; //项目名称
        $m_value = $pro_info['m_value']; //项目节点
        $m_zrdw = $pro_info['m_zrdw'];
        //当月投资显示
        if ($pro_info['type'] == 1 && trim($pro_info['m_value']) == '主体工程') {
            $m_account = 1;
        }

        //列表
        if ($month != '') {
            $progress_list = Progress::where(['custom_id' => $cid, 'pid' => $pid, 'month' => $month, 'p_year' => $p_year])->get()->toArray();

            //flag判断
            $flaginfo = Progress::where(['custom_id' => $cid, 'pid' => $pid, 'month' => $month])->where('p_status', '!=', 5)->orderBy('id', 'desc')->first();

            $ptime = date('n', $flaginfo['p_time']); //最新提交进度时间

            if ($m_zrdw != '') {
                $co_info = CoScore::where(['cid' => $cid, 'pid' => $pid, 'month' => $month, 'year' => $p_year])->first();

                //无数据 且当前月等于此节点月份 且填过进度 && $flaginfo 提前提交进度可以给协办打分 打的是当前节点月份分数
                if (!$co_info && ($now_m <= $month)) {
                    $xb_flag = 1;
                }

                if ($co_info['update_num'] == 1) {
                    $edit_flag = 1;
                }

                $m_zrdw_arr = explode(',', $m_zrdw);

                //查询单位表
                $corplist = Unit::whereIn('id', $m_zrdw_arr)->get();

                foreach ($corplist as $k_c => $v_c) {
                    $res_info = Project::where(['id' => $pid])->whereIn('units_id', [$v_c['id']])->first();
                    //如果 $res_info 存在说明就是自己单位的项目（自己也成了协办单位），所以就要去除
                    if ($res_info) {
                        unset($corplist[$k_c]);
                    }
                }
            }
            //当前节点协办得分列表 排除自己是协办的
            $co_list = CoScore::where(['cid' => $cid, 'pid' => $pid, 'month' => $month, 'year' => $p_year])->get();
            //循环 单位信息
            $unit_name = new Unit();
            //循环取出单位简称
            foreach ($co_list as $k => $v) {
                $co_list[$k]['units_name'] = $unit_name::where('id', $v['units_id'])->value('name');
            }
            if ($flaginfo['p_status'] == 4) {
                $flag = 1; //不可填写进度
            }
            if (($flaginfo['p_status'] == 4) && ($now_m == $month)) {
                //进度100% 切系统时间是当前计划月份 才能显示修改附件按钮
                $upload_flag = 1;
            }
            $y_time = '';
            $progress_view = array();

            foreach ($progress_list as $key => $value) {
                if ($value['y_time'] == 0) {
                    $y_time = '';
                } elseif ($value['y_time'] > 0) {
                    //提前完成
                    if ($value['p_status'] == 4) {
                        $y_time = '提前' . $value['y_time'] . '天';
                    } else {
                        $y_time = $value['y_time'] . '天';
                    }
                } else {
                    //逾期
                    $y_time = '逾期' . str_replace('-', '', $value['y_time']) . '天';
                }
                $progress_list[$key]['ytime'] = $y_time;
                //对应上传附件列表
//                $fileinfo_jb = M('upload_progress')->field('id,url,filename,mid,ext,type')->where('cid =' . $cid . ' and pid =' . $pid . ' and uid =' . $uid . ' and month=' . $month . ' and `mid`=' . $value['id'] . ' and is_delete=0 and type=1 ')->order('id desc')->select();
//                $fileinfo_fx = M('upload_progress')->field('id,url,filename,mid,ext,type')->where('cid =' . $cid . ' and pid =' . $pid . ' and uid =' . $uid . ' and month=' . $month . ' and `mid`=' . $value['id'] . ' and is_delete=0 and type=2 ')->order('id desc')->select();
                if (!empty($fileinfo_jb)) {
                    $progress_list[$key]['fileinfo_jb'] = $fileinfo_jb;
                }
                if (!empty($fileinfo_fx)) {
                    $progress_list[$key]['fileinfo_fx'] = $fileinfo_fx;
                }
                /* 新增2小时内可修改 */
                $diff_time = time() - $value['p_time'];

                if ($diff_time <= 3600 * 2) {
                    //2小时内才可显示修改进度按钮
                    $progress_list[$key]['pro_flag'] = 1;
                } else {
                    $progress_list[$key]['pro_flag'] = 0;
                }
                /* 新增2小时内可修改 */
            }
            //dump($progress_list);
            //如果已填写100% 去掉系统扫描记录
            $i = 0; //排序 去除 系统扫描
            foreach ($progress_list as $k => $v) {
                if ($v['p_status'] == 5) {
                    $progress_list[$k]['sort'] = '';
                } else {
                    $i++;
                    $progress_list[$k]['sort'] = $i;
                }
                if ($flag == 1) {
                    if ($v['p_status'] == 5) {
                        unset($progress_list[$k]);
                    }
                }
            }

            $prolist = $progress_list;
            foreach ($prolist as $key => $value) {
                if ($value['p_status'] == 5) {
                    unset($prolist[$key]);
                }
            }

            //dump($progress_list);
            //查找最新一条数据 显示逾期天数
            $progress_view = Progress::where(['custom_id' => $cid, 'pid' => $pid, 'month' => $month, 'p_year' => $p_year])->orderBy('p_time', 'desc')->first();

            if ($progress_view['p_status'] == 4) {
                $p_view['p_status'] = '已完成';
                if ($progress_view['y_time'] == 0) {
                    $p_view['y_time'] = '逾期天数：0';
                } elseif ($progress_view['y_time'] > 0) {
                    //提前完成
                    $p_view['y_time'] = '提前天数：' . $progress_view['y_time'];
                } else {
                    //逾期
                    $p_view['y_time'] = '逾期天数：' . str_replace('-', '', $progress_view['y_time']);
                }
            } else {
                if ($progress_view['y_time'] == 0) {
                    $p_view['p_status'] = '正常';
                    $p_view['y_time'] = '逾期天数：0';
                } elseif ($progress_view['y_time'] > 0) {
                    //提前完成
                    $p_view['p_status'] = '正常';
                    //$p_view['y_time'] = '';
                    $p_view['y_time'] = '提前天数：' . $progress_view['y_time'];
                } else {
                    //逾期
                    $min_y_time = $progress_view['y_time'];
                    if ($min_y_time < 0 && $min_y_time > -30) {
                        $p_view['p_status'] = '逾期';
                    } elseif ($min_y_time <= -30 && $min_y_time > -60) {
                        $p_view['p_status'] = '进展缓慢';
                    } elseif ($min_y_time <= -60) {
                        $p_view['p_status'] = '严重滞后';
                    }
                    $p_view['y_time'] = '逾期天数：' . str_replace('-', '', $progress_view['y_time']);
                }
            }
        }

        $res['other'] = [
            'pname' => '当前项目节点：' . $pname,
            'progress' => $p_view['p_status'],
            'node' => $m_value,
            'date' => $p_view['y_time'],
            'list_score' => $co_list,
        ];
        //0：无进度 1 : 25% 2 : 50% 3:75% 4:已完成 5:系统扫描未填
        $p_status = [
            0 => '无进度',
            1 => '25%',
            2 => '50%',
            3 => '75%',
            4 => '已完成',
            5 => '系统扫描未填'
        ];
        if ($progress_list) {
            foreach ($progress_list as $key => $val) {
                $progress_list[$key]['fileList1'] = Upload::where(['pid' => $val['pid'], 'relation_id' => $val['id'], 'type' => 1])->get()->toArray();
                $progress_list[$key]['fileList2'] = Upload::where(['pid' => $val['pid'], 'relation_id' => $val['id'], 'type' => 2])->get()->toArray();
            }
            foreach ($progress_list as $k => $v) {
                $v['p_status'] = $p_status[$v['p_status']];
                $res['progress_list'][$k] = $v;
            }
        } else {
            $res['progress_list'] = [];
        }
//        dd($res);
        return $this->success($res);

    }

    /**
     * 进图填报填写
     */
    public function write(ProgressRequest $request)
    {
        $now_m = date('n', time()); //当前月份
        $res_val = request()->all();
        $data = json_decode($res_val['data'], true);
        $cid = $data['cid'] ?? "";
        $month = $data['month'] ?? "";
        $m_account = 0; //是否显示当前节点投资金额
        $tq_flag = 0; //是否显示提前完成下拉
        $xb_flag = 0; //是否填写协办打分
        $pro_info = ProjectPlanCustom::with(['projectRes' => function ($q) use ($cid) {
            $q->select('id', 'pname', 'type');
        }])
            ->where('id', $cid)
            ->first();
        //项目ID
        $pid = $pro_info['pid'];

        //当月投资显示
        if (collect($pro_info)->isNotEmpty()) {
            $pro_info = $pro_info->toArray();
            $pro_info['pname'] = $pro_info['project_res']['pname'];
            $pro_info['type'] = $pro_info['project_res']['type'];
        }
        $months = 'content' . $month;
        //节点任务
        $mon_val = $pro_info[$months];

        if ($pro_info['type'] == 1 && trim($pro_info['m_value']) == '主体工程') {
            $m_account = 1;
        }

        //是否有责任单位
        $m_zrdw = $pro_info['m_zrdw'];
        $p_year = $pro_info['p_year'];


        //先判断月份 当前月份小于等于节点月份 且不等于12月份   可提前完成
        if ($now_m <= $month && $month != 12) {
            //判断此节点表
            $p_customs = ProjectPlanCustom::where('id', $cid)->select('p_month')->first();
            if (collect($p_customs)->isNotEmpty()) {
                $p_customs = $p_customs->toArray();
            }
            $p_custom = $p_customs['p_month'];

            if ($p_custom) {
                $arr_plan = explode(',', $p_custom); //过滤掉数组中的空值
                //要提前完成 不能只有1个月份
                if (count($arr_plan) != 1) {
                    if ($month != end($arr_plan)) {
                        //节点月份 不是最后一个月份 可提前完成
                        $tq_flag = 1;
                    }
                }
            }
        }
        //月份计划
        $p_progress = intval($month) . "月计划：" . $pro_info['m_value'];

        //  判断是否需要填写 协办打分

        if ($month != '') {
            $progress_list = Progress::where(['custom_id' => $cid, 'pid' => $pid, 'month' => $month, 'p_year' => $p_year])->get()->toArray();

            //flag判断
            $flaginfo = Progress::where(['custom_id' => $cid, 'pid' => $pid, 'month' => $month])->where('p_status', '!=', 5)->orderBy('id', 'desc')->first();

            $ptime = date('n', $flaginfo['p_time']); //最新提交进度时间

            //查询单位表
            $m_zrdw_arr = explode(',', $m_zrdw);
            $corplist = Unit::whereIn('id', $m_zrdw_arr)->select('id', 'name', 'alias_name')->get()->toArray();
            foreach ($corplist as $k => $v) {
                $corplist[$k]['rg_score'] = '0';
                $corplist[$k]['remark'] = '0';
            }

            if ($m_zrdw != '') {
                $co_info = CoScore::where(['cid' => $cid, 'pid' => $pid, 'month' => $month, 'year' => $p_year])->first();

                //无数据 且当前月等于此节点月份 且填过进度 && $flaginfo 提前提交进度可以给协办打分 打的是当前节点月份分数
                if (!$co_info && ($now_m <= $month)) {
                    $xb_flag = 1;
                }
            }
        }

        $progress_res = Progress::where(['pid' => $pid, 'custom_id' => $cid, 'month' => $month])->where('p_status', '!=', 5)
//            ->select('p_status')
            ->orderBy('p_time', 'desc')
            ->first();
        $p_status = $progress_res['p_status'];

        $status_arr = array();

        $status_arr[] = [
            'value' => 0,
            'name' => '无进度',
        ];
        $status_arr[] = [
            'value' => 1,
            'name' => '部分进度25%',
        ];
        $status_arr[] = [
            'value' => 2,
            'name' => '部分进度50%',
        ];
        $status_arr[] = [
            'value' => 3,
            'name' => '部分进度75%',
        ];
        $status_arr[] = [
            'value' => 4,
            'name' => '已完成',
        ];

        if ($tq_flag != 1) {
            $status_arr[] = [
                'value' => 99,
                'name' => '提前完成本环节',
            ];
        }

        //返回给前端 对应的 进度完成选择
        $status_arrs = array_slice($status_arr, $p_status);
        if (empty($status_arrs)) {
            $status_arrs = $status_arr;
        }

        $res['main'] = [
            'pid' => $pid,
            'p_year' => $p_year,
            'm_account' => $m_account,
            'xieban_flag' => $xb_flag,
            'p_progress' => $p_progress,
            'j_progress' => '节点任务:' . $mon_val,
            'corplist' => $corplist,
        ];

        $res['progress'] = $status_arrs;
        return $this->success($res);
    }

    /**
     * @param ProgressRequest $request
     * @return mixed
     * 进度填报提交
     */
    public function add(ProgressRequest $request)
    {
        $user = Auth::guard('api')->user();

        $res = $request->all();
        $datas = json_decode($res['data'], true);
        $fileList1 = json_decode($res['fileList1']);  //附件1 基本材料
        $fileList2 = json_decode($res['fileList2']);  //附件2 分项材料
//dd($datas['p_status']);
        //数据
        $data['uid'] = $data_m['uid'] = $user['id'];
        $data['pid'] = $data_m['pid'] = $datas['pid'];
        $data['custom_id'] = $data_m['custom_id'] = $datas['cid'] ?? '0';
        $data['month'] = $data_m['month'] = $datas['month'] ?? '0';
        //项目年份
        $data['p_year'] = $data_m['p_year'] = $datas['p_year'];
        $data['p_progress'] = $data_m['p_progress'] = $datas['p_progress'];
        $data['a_progress'] = $data_m['a_progress'] = $datas['a_progress'];
        $data['explain'] = $data_m['explain'] = $datas['explain'];
        $data['remarks'] = $data_m['remarks'] = $datas['remarks']; //填写进度备注
        $data['p_time'] = $data_m['p_time'] = time();
        $data['p_status'] = $datas['p_status'];

        //当月投资金额
        if ($datas['m_account']) {
            $data['m_account'] = $datas['m_account'];
        }

        //前台传过来的 xieban_flag
        $xieban_flag = $datas['xieban_flag'];

        $xieban = $datas['corplist'];

        //协办单位id
        $corp_ids = $request->input('units');

        //协办单位打分
        $rg_scores = $request->input('rg_score');

        //协办打分备注
        $remark = $request->input('remark');

        //协办单位名字
        $cnames = $request->input('name');

        //协办单位全称
        $fullnames = $request->input('alias_name');

        //判断单钩 双沟
        $pro_info = ProjectPlanCustom::where('id', $data['custom_id'])
            ->first()->toArray(); //节点自增ID
        //判断勾选进度百分比 不能小于 上一次勾选进度百分比
        $progress_res = Progress::where(['pid' => $data['pid'], 'custom_id' => $data['custom_id'], 'month' => $data['month']])->where('p_status', '!=', 5)
            ->orderBy('p_time', 'desc')
            ->first(); //节点自增ID

        $plan_custom_info = ProjectPlanCustom::getProjectAccount($data['custom_id']); //节点自增ID

        //181204 发改委新增需求 上
        //这里的代码集成到  progressrequest
        //181204 发改委新增需求 下

        // 请填写实际进度  在progressrequest 里面判断

        //实际进度的字数限制  在progressrequest 里面判断

        //进度未完成，请填写未完成原因  在progressrequest 里面判断

        //如果填写的  未完成，请填写未完成原因

        //如果填写的  未完成说明不能少于30字

        //未完成说明不能小于30字

        //如果 $xieban_flag ==1  那就要给协办打分

        //协办得分不能为空

        //请在备注中填写扣分点及需改进的建议

        //查询出来的节点 $pro_info['p_month']  转换成为数组

        $month_list = explode(',', trim($pro_info['p_month'], ','));
        $now_time = time();

        //$now_time = strtotime('2018-02-15');
        $now_m = $data['p_year'] . '-' . $data['month'];

        $today = date($now_m);

        //获取倒数7天的数据
        $last_day_arr = getthemonth($today);

        //当前月份最后一天
        $last_day = $last_day_arr[1];

        $datetime_s = date_create(date('Y-m-d', $data['p_time']));  //当前提交时间
        $time_now = date('Y-m-d', $data['p_time']);  //逾期计算有用
        $save = array();

        if (count($month_list) == 1) {
            //判断时间天数差
            $datetime_last = date_create($last_day);  //当月最后一天
            //如果当前时间戳 > 当月最后一天

            if ($now_time > strtotime($last_day)) {
                //逾期时间 -： 逾期
                $interval = date_diff($datetime_s, $datetime_last);   //判断时间天数差 当前时间  当月最后一天
                $y_time = $interval->format('%R%a');
                //计算是否逾期 不逾期 0 y_time 为0的话 也不会更新主表状态
                $overude = $this->checkOverdue($pro_info['pid'], $time_now, $data['month'], $data);
                $data['y_time'] = ($overude != 1) ? $y_time : 0;
                //dd($data);
                if ($data['p_status'] != 4) {
                    if ($y_time < 0 && $y_time > -30) {
                        //逾期 小于 30天
                        //$data['status'] = 3; //暂时没用这个字段
                        $save['pro_status'] = 3;
                        Project::where('id', '=', $data['pid'])->update(['pro_status' => $save['pro_status']]);
                    } elseif ($y_time <= -30 && $y_time > -60) {
                        //进展缓慢
                        //$data['status'] = 1;//暂时没用这个字段
                        //同时更新主表 pro_status =1
                        $save['pro_status'] = 1;
                        Project::where('id', '=', $data['pid'])->update(['pro_status' => $save['pro_status']]);
                    } elseif ($y_time <= -60) {
                        //严重滞后
                        //$data['status'] = 2; //暂时没用这个字段
                        //同时更新主表 pro_status =2
                        $save['pro_status'] = 2;
                        Project::where('id', '=', $data['pid'])->update(['pro_status' => $save['pro_status']]);
                    }
                }
            } else {
                if ($data['p_status'] == 4) {
                    //$data['status'] = 6; //暂时没用这个字段
                    //提前完成
                    $interval = date_diff($datetime_s, $datetime_last);   //判断时间天数差 当前时间  当月最后一天
                    $y_time = $interval->format('%R%a');
                    $data['y_time'] = $y_time;
                    //dd($y_time);
                }
            }

            if ($data['p_status'] == 4) {
                //同时更新主表 pro_status =0  月份纵向判断
                $save['pro_status'] = 0;
                Project::where('id', '=', $data['pid'])->update(['pro_status' => $save['pro_status']]);
            }

            //若填了进度而且进度不是0的，必须上传附件
//            if (!I('post.attachment_img') && $data['p_status'] > 0) {
//                $ajax['return'] = false;
//                $ajax['msg'] = "请上传附件，若没有项目材料，需单位文字说明作为附件上传";
//                $this->ajaxReturn($ajax);
//            }
            $result = Progress::create($data); //单月份插入记录

            if ($result) {
                Project::where('id', '=', $data['pid'])->update(['progress' => get_progresswidth($data['pid'])]);
            }

            //更新上传进度表 mid
//            if (I('post.attachment_img')) {
//                $fileids = rtrim(I('post.attachment_img'), ',');
//                $sql = "update `wh_upload_progress` set `mid`=" . $result . " where id in (" . $fileids . ")";
//                M()->execute($sql);
//            }
        } elseif (count($month_list) > 1) {

            //双沟 多沟
            $n_month = date('n', $data['p_time']);
            $last_m_day = $data['p_year'] . '-' . end($month_list); //最后一个沟 当月最后一天

            $datetime_last = date_create($last_day);  //当月最后一天

            $end_day = $last_day;

            //判断当前时间月份 是否在计划内 且不是最后一月时 计算提前完成时间 按最后一月的 最后一天
            if ((in_array($n_month, $month_list)) && ($n_month != end($month_list))) {
                //如果当前时间戳 > 当月最后一天

                if ($now_time > strtotime($end_day)) {
                    //逾期完成
                    $interval = date_diff($datetime_s, $datetime_last);   //判断时间天数差 当前时间  当月最后一天
                    $y_time = $interval->format('%R%a');
                    //计算是否逾期 不逾期 0 y_time 为0的话 也不会更新主表状态
                    $overude = $this->checkOverdue($pro_info['pid'], $time_now, $data['month'], $data);
                    $data['y_time'] = ($overude != 1) ? $y_time : 0;
                    if ($data['p_status'] != 4) {
                        if ($y_time < 0 && $y_time > -30) {
                            //逾期 小于 30天
                            //$data['status'] = 3;
                            $save['pro_status'] = 3;
                            Project::where('id', '=', $data['pid'])->update(['pro_status' => $save['pro_status']]);
                        } elseif ($y_time <= -30 && $y_time > -60) {
                            //进展缓慢
                            //$data['status'] = 1;
                            //同时更新主表 pro_status =1
                            $save['pro_status'] = 1;
                            Project::where('id', '=', $data['pid'])->update(['pro_status' => $save['pro_status']]); // 根据条件更新记录
                        } elseif ($y_time <= -60) {
                            //严重滞后
                            //$data['status'] = 2;
                            //同时更新主表 pro_status =2
                            $save['pro_status'] = 2;
                            Project::where('id', '=', $data['pid'])->update(['pro_status' => $save['pro_status']]); // 根据条件更新记录
                        }
                    }
                } else {
                    //提前完成
                    if ($data['p_status'] == 4) {
                        //$data['status'] = 6;
                        $end_time = $datetime_last;
                        $interval = date_diff($datetime_s, $end_time);
                        $y_time = $interval->format('%R%a');
                        $data['y_time'] = $y_time;
                    }
                }
                if ($data['p_status'] == 4) {
                    //同时更新主表 pro_status =0  月份纵向判断
                    $save['pro_status'] = 0;
                    Project::where('id', '=', $data['pid'])->update(['pro_status' => $save['pro_status']]);//根据条件更新记录
                }
            } else {
                //  逾期时间
                if ($now_time > strtotime($end_day)) {
                    //逾期完成
                    $interval = date_diff($datetime_s, $datetime_last);   //判断时间天数差 当前时间  当月最后一天
                    $y_time = $interval->format('%R%a');
                    //计算是否逾期 不逾期 0 y_time 为0的话 也不会更新主表状态
                    $overude = $this->checkOverdue($pro_info['pid'], $time_now, $data['month'], $data);
                    $data['y_time'] = ($overude != 1) ? $y_time : 0;
                    if ($data['p_status'] != 4) {
                        if ($y_time < 0 && $y_time > -30) {
                            //逾期 小于 30天
                            //$data['status'] = 3;
                            $save['pro_status'] = 3;
                            Project::where('id', '=', $data['pid'])->update(['pro_status' => $save['pro_status']]);
                        } elseif ($y_time <= -30 && $y_time > -60) {
                            //进展缓慢
                            //$data['status'] = 1;
                            //同时更新主表 pro_status =1
                            $save['pro_status'] = 1;
                            Project::where('id', '=', $data['pid'])->update(['pro_status' => $save['pro_status']]); // 根据条件更新记录
                        } elseif ($y_time <= -60) {
                            //严重滞后
                            //$data['status'] = 2;
                            //同时更新主表 pro_status =2
                            $save['pro_status'] = 2;
                            Project::where('id', '=', $data['pid'])->update(['pro_status' => $save['pro_status']]); // 根据条件更新记录
                        }
                    }
                } else {
                    //提前完成 多沟
                    if ($data['p_status'] == 4) {
                        //$data['status'] = 6;
                        $end_time = $datetime_last;
                        $interval = date_diff($datetime_s, $end_time);
                        $y_time = $interval->format('%R%a');
                        $data['y_time'] = $y_time;
                    }
                }
                if ($data['p_status'] == 4) {
                    //同时更新主表 pro_status =0
                    $save['pro_status'] = 0;
                    Project::where('id', '=', $data['pid'])->update(['pro_status' => $save['pro_status']]); // 根据条件更新记录
                }
            }
            if ($data['p_status'] == 4) {
                //同时更新主表 pro_status =0
                $save['pro_status'] = 0;
                Project::where('id', '=', $data['pid'])->update(['pro_status' => $save['pro_status']]);// 根据条件更新记录
            }
            //若填了进度而且进度不是0的，必须上传附件
//            if (!I('post.attachment_img') && $data['p_status'] > 0) {
//                $ajax['return'] = false;
//                $ajax['msg'] = "请上传附件，若没有项目材料，需单位文字说明作为附件上传";
//                $this->ajaxReturn($ajax);
//            }
            /**
             * 勾选提前完成 同步插入数据 start
             */
            $data_m['y_time'] = $data['y_time'];
            if ($data['p_status'] == 99) {
                $first = '';
                $month_key = array_keys($month_list, $request->input('month')); //取出填写月份key
                $monthlist_new = array_slice($month_list, $month_key[0]); //生成新月份数组

                foreach ($monthlist_new as $k_m => $v_m) {
                    $data_m['month'] = $v_m;
                    $data_m['p_status'] = 4;
                    //插入进度数据 需要判断逾期还是提前
                    if (intval($v_m) != intval($request->input('month'))) {
                        $now_m_tq = $data['p_year'] . '-' . $v_m;
                        $today_tq = date($now_m_tq);
                        $last_day_arr_tq = getthemonth($today_tq);

                        $last_day_tq = $last_day_arr_tq[1]; //当前月份最后一天

                        $datetime_last_tq = date_create($last_day_tq);

                        $datetime_s_tq = date_create(date('Y-m-d', $data['p_time']));

                        $interval_tq = date_diff($datetime_s_tq, $datetime_last_tq);

                        $y_time_tq = $interval_tq->format('%R%a');

                        $data_m['y_time'] = $y_time_tq;
                    }

                    $result = Progress::create($data_m); //单月份插入记录
                    $result = $result->toArray();
                    $first .= $result['id'] . ',';
                }

                $result_arr = explode(',', trim($first, ','));
                $result_month = $request->input('month');
                $result_key = array_search($result_month, $monthlist_new); // 取当前月份key
                $result_id = $result_arr[$result_key];

                //更新上传进度表 mid
//                if (I('post.attachment_img')) {
//                    $fileids = rtrim(I('post.attachment_img'), ',');
//                    $sql = "update `wh_upload_progress` set `mid`=" . $result_id . " where id in (" . $fileids . ")";
//                    M()->execute($sql);
//                }
                //更新进度表成功后 先查询进度附件表的数据
                $monthlist_copy = $monthlist_new;
                unset($monthlist_copy[0]); //去除当前填写月份下标
                //print_r($monthlist_copy);exit;
                //复制附件表内容 运用mysql insert
                if (!empty($monthlist_copy)) {
                    foreach ($monthlist_copy as $k_m => $v_m) {
                        $m_progress_p = Progress::where(['pid' => $data['pid'], 'custom_id' => $data['custom_id'], 'month' => $v_m])->get()->toArray();
                        if ($m_progress_p) {
//                            $sql_insert = "INSERT INTO `wh_upload_progress` (pid,cid,uid,url,add_time,filename,file_new_name,ext,`type`,mid,`month`)
//SELECT pid,cid,uid,url,add_time,filename,file_new_name,ext,`type`,{$m_progress_p['id']},{$v_m} FROM `wh_upload_progress` WHERE pid = {$data['pid']} and cid={$data['custom_id']} and month={$data['month']}";
//                            M()->execute($sql_insert);
                        }
                    }
                }
            } else {
                //dd($data);
                $result = Progress::create($data); //单月份插入记录

                //更新上传进度表 mid
//                if (I('post.attachment_img')) {
//                    $fileids = rtrim(I('post.attachment_img'), ',');
//                    $sql = "update `wh_upload_progress` set `mid`=" . $result . " where id in (" . $fileids . ")";
//                    M()->execute($sql);
//                }
            }
            /**
             * 勾选提前完成 同步插入数据 end
             */
            if ($result) {
                Project::where('id', '=', $data['pid'])->update(['progress' => get_progresswidth($data['pid'])]);
            }
        }

        $id_list = Progress::where(['p_status' => '4', 'pid' => $data['pid']])->get()->toArray(); //节点自增ID

        //月份 纵轴判断  and month=' . $data['month'] . '
        $ids = '';
        foreach ($id_list as $k_id => $v_id) {
            $ids_item = Progress::where(['pid' => $v_id['pid'], 'custom_id' => $v_id['custom_id'], 'month' => $v_id['month']])->get()->toArray(); //节点自增ID
            foreach ($ids_item as $k_item => $v_item) {
                $ids .= $v_item['id'] . ','; // 节点对应月份相关所有进度id 为更新主表状态 排除这些id
            }
        }
        $ids = trim($ids, ',');
        $ids_arr = explode(',', $ids);

        $where_p = [
            ['pid', '=', $data['pid']],
            ['p_year', '=', $data['p_year']],
            ['p_status', '!=', 4],
        ];
        if ($ids != '') {
            $progress_month_list = Progress::where($where_p)->whereNotIn('id', $ids_arr)->orderBy('id', 'desc')->get()->toArray(); //节点自增ID
        } else {
            $progress_month_list = Progress::where($where_p)->orderBy('id', 'desc')->get()->toArray(); //节点自增ID
        }

        $progress_pid = array();
        foreach ($progress_month_list as $k_zz => $v_zz) {
            $progress_pid[$v_zz['pid']][]['y_time'] = $v_zz['y_time'];
        }

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
                Project::where('id', '=', $k_pid)->update(['pro_status' => $save['pro_status']]);// 更新主表状态
            } elseif ($count_v > 1) {
                $min_list = min($v_item); //取最大 逾期天数  逾期为负数 所以用 min()

                $min_y_time = $min_list['y_time'];

                if ($min_y_time < 0 && $min_y_time > -30) {
                    $save['pro_status'] = 3;
                } elseif ($min_y_time <= -30 && $min_y_time > -60) {
                    $save['pro_status'] = 1;
                } elseif ($min_y_time <= -60) {
                    $save['pro_status'] = 2;
                } else {
                    $save['pro_status'] = 0;
                }
                Project::where('id', '=', $k_pid)->update(['pro_status' => $save['pro_status']]); // 更新主表状态
            }
        }

        if ($result) {
            //获取改节点项目投资金额总数是否大于500
            $pro_val = Project::where(['id' => $pro_info['pid']])->first();
            $is_incor = Natong::where(['pid' => $pro_info['pid']])->first();
            $progress_jindu = Progress::where(['id' => $result['id']])->first();
            if ($pro_val['type'] == 1 || $pro_val['type'] == 2) {
                if ($pro_val['amount'] >= 500 && $pro_info['m_value'] == '主体工程' && $progress_jindu['p_status'] >= 1 && $is_incor['is_incor'] != 1 && $is_incor['is_incor'] != 2 && $is_incor['is_incor'] != 12) {
                    $pro_save = Natong::where('pid', '=', $pro_info['pid'])->update(['is_incor' => '10']);
                    $natong_data['pid'] = $pro_info['pid'];
                    $natong_data['natong_status'] = '10';
                    $natong_data['uid'] = '0';
                    $natong_data['edit_time'] = time();
                    //更新记录表
                    NatongRecord::create($natong_data); //单月份插入记录

//                    if ($pro_save) {
//                        $content = '您单位的项目' . $pro_val['pname'] . '记为应统未统，请登录后台查看。';
//                        $data['uid'] = array($data['uid']);
//                        send_message($data['uid'], $content, 6, $pro_info['pid']);
//                    }
                }
            }

            //如果进度是100 判断最后一次填报日期 是否超日历进度
            $pro_grogress = Project::where(['id' => $data['pid']])->first();
            if ($pro_grogress['progress'] == 100) {

                $progress = Progress::where('id', $result['id'])->where('p_status', '!=', 5)->first();//最后一次提交日期
                if ($progress && ($progress['y_time'] > 0)) {
                    //更新主表 提前字段
                    Project::where('id', '=', $data['pid'])->update(['advance_day' => $progress['y_time']]);
                }
            }
            //进度附件上传
            if (!empty($fileList1)) {
                $pid = $data['pid'];
                $relation_id = $result['id'];
                $uid = $data['uid'];
                Upload::upload($pid, $relation_id, $uid, 2, $fileList1, 1);
            }
            if (!empty($fileList2)) {
                $pid = $data['pid'];
                $relation_id = $result['id'];
                $uid = $data['uid'];
                Upload::upload($pid, $relation_id, $uid, 2, $fileList2, 2);
            }
            //插入协办打分
            if ($xieban_flag == 1) {
                $CoScore = new CoScore();
                foreach ($xieban as $key => $v) {
                    $CoScore->uid = $data['uid'];
                    $CoScore->units_id = $v['id'];
                    $CoScore->cid = $data['custom_id'];
                    $CoScore->pid = $data['pid'];
                    $CoScore->rg_score = $v['rg_score'];
                    $CoScore->remark = $v['remark'];
                    $CoScore->month = $data['month'];
                    $CoScore->year = $data['p_year'];
                    $res_pro = $CoScore->save();
                }
            }
            return $this->success('添加成功');
        } else {
            return $this->failed('添加失败！');
        }
    }

    /*
     * 进度提交审核
     */
    public function pass(ProgressRequest $request)
    {

        $res = $request->all();
        $datas = json_decode($res['data'], true);
        $progress_id = $datas['progress_id'];
        $month = $datas['month'];

        //如果获取到了填报的节点id 1、更改节点状态 status，然后 更新p_time字段/2、更新 wh_co_score表的addtime字段
        if ($progress_id) {
            $res_cid = Progress::where('id', $progress_id)->value('custom_id');
            $res_isset = CoScore::where(['cid' => $res_cid, 'month' => $month])->where('status', 0)->get()->toArray();

            DB::beginTransaction();
            try {
//                $data_arr = [
//                    'status' => 1,
//                    'p_time' => time(),
//                ];
                $res_progress = Progress::where('id', '=', $progress_id)->update(['status' => 1, 'p_time' => time()]);
                if (!empty($res_isset)) {
                    $res_score = CoScore::where(['cid' => $res_cid, 'month' => $month])->update(['status' => 1, 'addtime' => time()]);

                } else {
                    $res_score = 1;

                }
                if ($res_progress && $res_score) {
                    DB::commit();
                    return $this->success('操作成功');
                } else {
                    DB::rollBack();
                    return $this->failed('操作失败');
                }
            } catch (Exception $e) {
                DB::rollBack();
                echo $e->getMessage();
            }
        } else {
            return $this->failed('请传入合法的填报节点id！');
        }
    }

    /*
     * 填报进度修改
     */
    public function edit(ProgressRequest $request)
    {
        $type = $request->method();
        if ($type == 'GET') {
            $res = $request->all();
            $datas = json_decode($res['data'], true);
            $progress_id = $datas['progress_id'];
            if ($progress_id) {
                $res_cid = Progress::with(['progressRes' => function ($q) use ($progress_id) {

                }])
                    ->where('id', $progress_id)
                    ->first()
                    ->toArray();
                $res_cid['remarks'] = $res_cid['remark'];
                $mont_val = $res_cid['progress_res']['content' . $res_cid['month']] ?? '';
                $res_cid['j_progress'] = $mont_val;
                unset($res_cid['remark']);

                if ($res_cid['status'] == 0) {
                    //通过  custom_id  查询评分相关的内容
                    $res_score = CoScore::where('cid', $res_cid['custom_id'])->get()->toArray();
                    //基本材料
                    $data['fileList1'] = Upload::where(['pid' => $res_cid['pid'], 'relation_id' => $res_cid['id'], 'type' => 1])->get()->toArray();
                    //分项材料
                    $data['fileList2'] = Upload::where(['pid' => $res_cid['pid'], 'relation_id' => $res_cid['id'], 'type' => 2])->get()->toArray();
                    $data['progress'] = $res_cid;
                    $data['corplist'] = $res_score;
                    return $this->success($data);
                } else {
                    return $this->failed('已经审核过的不能修改');
                }
            } else {
                return $this->failed('节点id不对！');
            }
        } else if ($type == 'POST') {
            $user = Auth::guard('api')->user();

            $res = $request->all();
            $datas = json_decode($res['data'], true);
            $fileList1 = json_decode($res['fileList1']);  //附件1 基本材料
            $fileList2 = json_decode($res['fileList2']);  //附件2 分项材料
            $pid = $datas['pid'];
            if (!$pid) {
                return $this->failed('未找到pid');
            }
            $progress_id = $datas['progress_id'];
            $p_status = $datas['p_status'];
            $a_progress = $datas['a_progress'];
            $m_account = $datas['account'] ?? '0';
            $explain = $datas['explain'];
            $remarks = $datas['remarks'];

            //协办打分
            $corplist = $datas['corplist'];
            //dump($progress_id);exit;

            DB::beginTransaction();
            try {
                $res_progress = Progress::find($progress_id);
                $res_progress->p_status = $p_status;
                $res_progress->a_progress = $a_progress;
                $res_progress->remark = $remarks;
                $res_progress->m_account = $m_account;
                $res_progress->p_status = $p_status;
                $res_save = $res_progress->save();

                foreach ($corplist as $k => $v) {
                    $res_score = CoScore::where('id', $v['id'])->update(['rg_score' => $v['rg_score'], 'remark' => $v['remark']]);
                }
                //dump($res_progress);
                //dd($res_score);
                $proj = Project::where(['id' => $pid])->first();
                $uid = $proj->uid;
                Upload::where(['pid' => $pid, 'relation_id' => $progress_id, 'file_type' => 2])->delete();
                $relation_id = $progress_id;

                if ($res_save && $res_score) {
                    DB::commit();
                    //进度附件上传
                    if (!empty($fileList1)) {
                        Upload::upload($pid, $relation_id, $uid, 2, $fileList1, 1);
                    }
                    if (!empty($fileList2)) {
                        Upload::upload($pid, $relation_id, $uid, 2, $fileList2, 2);
                    }
                    return $this->success('操作成功');
                } else {
                    DB::rollBack();
                    return $this->failed('操作失败');
                }
            } catch (Exception $e) {
                DB::rollBack();
                echo $e->getMessage();
            }

        } else {
            return $this->failed('提交方式错误');
        }

    }

    /**
     * Notes: 项目逾期新计算规则
     * @param $pid  项目ID
     * @param $datetime_s 填报时间
     * @param $v_m  当前月份
     * @param $data 当前数据
     * @return int 为1 不计算逾期
     */
    public function checkOverdue($pid, $datetime_s, $v_m, $data)
    {
        $result = 0;
        /** 逾期补填 信息**/
        $env_wad_day = (int)Option::where('key', 'wad_day')->value('value');//补填天数配置
        $uptime_info = Project::find($pid)->uptime; //项目入库时间
        $uptime = date('Y-m-d', $uptime_info); //项目入库时间
        $uptime_m = date('m', $uptime_info);//入库月份
        $wadtime = date("Y-m-d", strtotime("$uptime +$env_wad_day day"));   //补填时间
        $wadtime_m = date('m', strtotime($wadtime)); //补填月份
        /** 逾期补填 信息**/
        /**新增入库后项目逾期处理方式 start **/
        $k_month = getMonthNum($wadtime, $uptime);//补填时间 跟入库时间 差值是否垮月

        if (($datetime_s <= $wadtime) && ($k_month == 0)) {
            //当前时间还在填报时间内的 并且没有跨月的
            if ($v_m < $uptime_m) {
                //小于入库月份的 不计算逾期
                $result = 1;
            }
        } elseif (($wadtime > $datetime_s) && ($k_month == 0)) {
            //当前时间不在填报时间内的 并且没有跨月的
            if (($v_m < $uptime_m) && ($data['p_status'] == 4)) {
                //小于入库月份的 补填为100%的进度 不计算逾期
                $result = 1;
            }
        } elseif (($datetime_s <= $wadtime) && ($k_month > 0)) {
            //当前时间还在填报时间内的 并且跨月的
            if ($v_m < $wadtime_m) {
                //小于补填月份的 不计算逾期
                $result = 1;
            }
        } elseif (($wadtime > $datetime_s) && ($k_month > 0)) {
            //当前时间不在填报时间内的 并且跨月的
            if (($v_m < $wadtime_m) && ($data['p_status'] == 4)) {
                //小于补填月份的 补填为100%的进度 不计算逾期
                $result = 1;
            }
        }
        /**新增入库后项目逾期处理方式 end **/
        return $result;
    }

    /**
     * 批量审核列表
     */
    public function listpass(ProgressRequest $request)
    {
        $type = $request->method();
        if ($type == 'GET') {
            $res = $request->all();
            $datas = json_decode($res['data'], true);
//            dump($datas);
            $own_unit = $datas['units_id'] ?? '0';
            if (count($datas['role_id']) > 1) {
                $group_role = max($datas['role_id']) ?? '0';
            } else {
                $group_role = $datas['role_id'][0] ?? '0';
            }

            if ($group_role != '29') {
                return $this->failed('该用户非单位领导');
            }

            //先找出自己的项目
            $pro_info = new ProgressCollection(Project::with(['progressWrite' => function ($q) use ($own_unit) {
                $q->select('id', 'pid', 'p_progress', 'p_time');
            }])
                ->whereHas('progressWrite', function ($e) {
                    $e->where('status', '!=', 1);
                })
                ->where('units_id', $own_unit)
                ->select('id', 'pname', 'type', 'pro_status', 'tianbiao_date', 'fen_uid', 'type', 'year', 'is_year', 'progress')
                ->paginate(20));

            return $this->success($pro_info);

        } else if ($type == 'POST') {
            $res = $request->all();
            $pid_arr = json_decode($res['data'], true);

            DB::beginTransaction();
            try {
//                $data_arr = [
//                    'status' => 1,
//                    'p_time' => time(),
//                ];
                $pro_val = Progress::whereIn('pid', $pid_arr)->where('status', '!=', 1)->select('id', 'custom_id', 'month')->get()->toArray();

                if ($pro_val) {
                    foreach ($pro_val as $k => $v) {
                        $res_progress = Progress::where('id', $v['id'])->where('status', '!=', 1)->update(['status' => 1, 'p_time' => time()]);
                    }
                }


                if (!empty($res_progress)) {
                    foreach ($pro_val as $k => $v) {
                        $res_isset = CoScore::where(['cid' => $v['custom_id'], 'month' => $v['month']])->where('status', 0)->get()->toArray();
                        if (!empty($res_isset)) {
                            $res_score = CoScore::where(['cid' => $v['custom_id'], 'month' => $v['month']])->update(['status' => 1, 'addtime' => time()]);
                        }
                    }

                } else {
                    $res_score = 1;

                }

                if ($res_score) {
                    DB::commit();
                    return $this->success('操作成功');
                } else {
                    DB::rollBack();
                    return $this->failed('操作失败');
                }

            } catch (Exception $e) {
                DB::rollBack();
                echo $e->getMessage();
            }
        } else {
            return $this->failed('提交方式错误');
        }

        //获取到了组id和单位id就查询这条线上的项目
    }

    /**
     * 季进度展示
     */
    public function progressJidu(ProgressRequest $request)
    {

        $res_val = request()->all();
        $data = json_decode($res_val['data'], true);
        //获取原来的项目id，然后去进度表去查询相关进度
        $pro_id = $data['pid'] ?? '0';

        if ($pro_id) {
            $res_val = Project::where('id', $pro_id)->select('pname', 'target', 'fen_uid', 'zhuban', 'zhu_fuze')->first()->toArray();
        }
        $res_val['fen_name'] = \App\Models\User::find($res_val['fen_uid'])->username ?? '暂无';

        //通过id去进度表查询相关数据 取出计划进度p_progress，实际进度a_progress，未完成原因explain
//        $order = 'p_time asc,month asc';
//        $where['p_status'] = array('neq', 5);
//        $where['pid'] = $pro_id;
        $res_progress = Progress::where('p_status', '!=', 5)->where('pid', $pro_id)->orderBy('p_time', 'ASC')->orderBy('month', 'ASC')->get();

        //循环分别给实际进度和未完成原因加上相关字段
        if ($res_progress) {
            foreach ($res_progress as $k => $v) {
                $v['p_progress'] = str_replace("计划", '', $v['p_progress']);
                if (!empty($v['a_progress'])) {
                    $v['a_progress'] = $k . '月 : ' . $v['a_progress'];
                }
                if (!empty($v['explain'])) {
                    $v['explain'] = $k . '月 : ' . $v['explain'];
                }
                $res_progress[$k] = $v;
            }
        }

        //不存在的月份也要处理成一个空数组
        $res_progress_null['month'] = '';
        $res_progress_null['p_progress'] = '';
        $res_progress_null['a_progress'] = '';
        $res_progress_null['explain'] = '';
        for ($i = 1; $i <= 12; $i++) {
            if (!isset($res_progress[$i])) {
                $res_progress[$i] = $res_progress_null;
            }
        }
        //循环成一个为4个下标的数组
//        $res_progress_all = array();
//        foreach ($res_progress as $k => $v) {
//            if ((1 <= $k) && ($k <= 3)) {
//                $res_progress_all[1][$k] = $v;
//            }
//            if ((4 <= $k) && ($k <= 6)) {
//                $res_progress_all[2][$k] = $v;
//            }
//            if ((7 <= $k) && ($k <= 9)) {
//                $res_progress_all[3][$k] = $v;
//            }
//            if ((10 <= $k) && ($k <= 12)) {
//                $res_progress_all[4][$k] = $v;
//            }
//        }
        $datas['proval'] = $res_val;
        $datas['progress'] = $res_progress;
        return $this->success($datas);
    }

    /**
     * 待填报的进度数量
     */
    public function progressCount(ProgressRequest $request)
    {
        //获取到当前人员的id
        $user = Auth::guard('api')->user()->toArray();
        $uid = $user['id'];
        $month = str_replace("0", "", date('m'));

        //先找出自己的项目
        $own_pro_arr = Project::where('uid', $uid)->select()->pluck('id')->toArray();

        //然后节点表关联进度表查询当前月份待填报的项目
        //只能先找出填报了等当前的月份，然后数组对比去重，剩下的就是当前月份每填报
        $pro_val_yitian = ProjectPlanCustom::with(['customRes' => function ($q) use ($month) {
            $q->select('id', 'pid', 'custom_id', 'month');
//                ->whereNotIn('month', array($month));
        }])
            ->whereHas('customRes', function ($e) use ($month) {
                $e->where('month', $month);
            })
            ->select('id', 'pid', 'm_value', 'p_month')
            ->whereIn('pid', $own_pro_arr)
            ->whereRaw(" find_in_set({$month}, p_month)")
            ->groupBy('pid')
            ->pluck('pid')
            ->toArray();

        $pro_val_all = ProjectPlanCustom::with(['customRes' => function ($q) use ($month) {
            $q->select('id', 'pid', 'custom_id', 'month');
//                ->whereNotIn('month', array($month));
        }])
//            ->whereHas('customRes', function ($e) use ($month) {
//                $e->where('month',$month);
//            })
            ->select('id', 'pid', 'm_value', 'p_month')
            ->whereIn('pid', $own_pro_arr)
            ->whereRaw(" find_in_set({$month}, p_month)")
            ->groupBy('pid')
            ->pluck('pid')
            ->toArray();

        $pro_val_weitian_id = array_diff($pro_val_all, $pro_val_yitian);
        $pro_val_weitian = Project::whereIn('id', $pro_val_weitian_id)->get()->toArray();
        return $pro_val_weitian;
    }

    //填报待审核
    public function progressDai(ProgressRequest $request)
    {
        $user = Auth::guard('api')->user()->toArray();
        $uid = $user['id'];
        //找出自己单位的项目
        $own_pro_arr = Project::where('uid', $uid)->select()->pluck('id')->toArray();
        //找到项目
        $progressDai = Progress::whereIn('pid', $own_pro_arr)->where('status', 0)->get()->toArray();
        return $progressDai;
    }
}

