<?php

namespace App\Service;

use App\Http\Resources\Api\V1\Frontend\ProjectCollection;
use App\Http\Resources\Api\V1\Frontend\ProjectResource;
use App\Models\Progress;
use App\Work\Model\FlowProcess;
use App\Work\Model\RunProcess;
use App\Work\Repositories\ProcessRepo;
use App\Work\Workflow;
use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Auth;

/**
 * Class PendingService
 * @package App\Service
 * 项目公共类
 */
class ProjectService
{

    //项目列表 根据个角色 按条件查询
    public function getProject(Request $request)
    {
        $user = Auth::guard('api')->user();
        //$role_id = Auth::guard('api')->user()->role_id ??1;
        $uid = $request->get('uid') ?? 1;//测试接口用
        $role_id = (is_array($request->get('role_id'))) ? $request->get('role_id')[0] :$request->get('role_id');//测试接口用
        //dd($request->get('role_id'));
        $menu = $request->get('menu') ?? 'dsh';//菜单url
        //$is_paginate = $request->get('is_paginate') ?? true; //是否开启分页 默认开启
        $paginate = $request->get('per_page') ?? 15;
        $wf_type = $request->get('project') ?? 'project';//关联业务表 默认是项目表
        $result = [];
        $where_project_push=[];
        //主表条件查询
        $where_project = [
            ['is_report', '=', 1]
        ];
        $flow_process_ids = null;
        $map = [];//条件查询
        //公共菜单部分
        if($menu == 'qsxm'){ //全市项目
            $where_project_push =['status_flow', '=', 2];
            array_push($where_project,$where_project_push);//追加条件
        }
        if($menu == 'yq'){ //全市所有逾期项目（包括逾期，进展缓慢、严重滞后）
            $where_project_push_1 =['status_flow', '=', 2];
            $where_project_push_2 =['pro_status', '!=', 0];
            array_push($where_project,$where_project_push_1,$where_project_push_2);//追加条件
        }
        if ($role_id == '1') { //立项单位 操作员
            //通过流程id 及步骤名称 找到步骤ID
            //$flow_id = Run::getflow_id($uid, $wf_type)->flow_id;
            //$flow_keshi_id = FlowProcess::getprocessId($flow_id, '科室')->id;

//            if ($menu == 'dks') { //待科室审核
//                $map = [
//                    ['wf_run.uid', '=', $uid],
//                    ['wf_run.run_flow_process', '=', $flow_keshi_id],
//                    ['wf_run.status', '=', 0]
//                ];
//            } elseif ($menu == 'dld') { //待领导审核
//                $map = [
//                    ['wf_run.run_flow_process', '<>', $flow_keshi_id],
//                    ['wf_run.status', '=', 0]
//                ];
//            }
            if ($menu == 'dsh') { //待审核 查看当前用户发起立项申请后还在审核中，未入库的项目
                $map = [
                    ['wf_run.uid', '=', $uid],
                    ['wf_run.status', '=', 0]
                ];
            }elseif ($menu == 'bh') { //驳回列表
                $map = [
                    ['wf_run.uid', '=', $uid],
                    ['wf_run_log.btn', '=', 'Back'],
                ];
                $where_project_push =['status_flow', '=', '-1'];
                array_push($where_project,$where_project_push);//追加条件
            } elseif ($menu == 'jsz') { //建设中
                $map = [
                    ['wf_run.uid', '=', $uid]
                ];
                $where_project_push =['status_flow', '=', '2'];
                array_push($where_project,$where_project_push);//追加条件
            }elseif($menu == 'bdw'){ //本单位所有在建项目（包括其他操作员立项的项目）
                $unit_id = $user->units_id ?? 0;  //查出当前用户 单位id
                if($unit_id !=0){
                    $where_project_push_1 =['status_flow', '=', '2'];
                    $where_project_push_2 =['units_id', '=', $unit_id];
                    array_push($where_project,$where_project_push_1,$where_project_push_2);//追加条件
                }
            }
        } elseif ($role_id == '2') { //业务科室
            $process_name = '科室';
            if ($menu == 'yq') { //经过当前用户审核入库的逾期项目（包括逾期，进展缓慢、严重滞后）
                $flow_process_ids = FlowProcess::getprocessIdsByflow_id($uid, $process_name);
            }
        } elseif ($role_id == '3') {//副秘书长
            $process_name = '副秘书长';
            if ($menu == 'yq') { //经过当前用户审核入库的逾期项目（包括逾期，进展缓慢、严重滞后）
                $flow_process_ids = FlowProcess::getprocessIdsByflow_id($uid, $process_name);
            }
        } elseif ($role_id == '4') {//分管副市长
            $process_name = '分管副市长';
            if ($menu == 'yq') { //经过当前用户审核入库的逾期项目（包括逾期，进展缓慢、严重滞后）
                $flow_process_ids = FlowProcess::getprocessIdsByflow_id($uid, $process_name);
            }
        } elseif ($role_id == '5') { //五化办
            $process_name = '五化办';
        } elseif ($role_id == '6') { //常务副市长
            $process_name = '常务副市长';
        } elseif ($role_id == '7') { //市长
            $process_name = '市长';
        }elseif ($role_id == '8') { //立项单位 单位领导
            $unit_id = $user->units_id ?? 0;  //查出当前用户 单位id
            if($unit_id !=0){
                $where_project_push =['units_id', '=', $unit_id];
                array_push($where_project,$where_project_push);//追加条件
            }
            if ($menu == 'dsh') { //待审核 本单位发起立项申请后还在审核中，未入库的项目
                $map = [
                    ['wf_run.status', '=', 0]
                ];
            }elseif ($menu == 'bh') { //驳回列表
                $map = [
                    ['wf_run_log.btn', '=', 'Back'],
                ];
                $where_project_push =['status_flow', '=', '-1'];
                array_push($where_project,$where_project_push);//追加条件
            } elseif ($menu == 'jsz') { //建设中
                $where_project_push_status =['status_flow', '=', '2'];
                array_push($where_project,$where_project_push_status);//追加条件
            }elseif($menu == 'yq'){ //全市所有逾期项目（包括逾期，进展缓慢、严重滞后）
                $where_project_push_1 =['status_flow', '=', 2];
                $where_project_push_2 =['pro_status', '!=', 0];
                array_push($where_project,$where_project_push_1,$where_project_push_2);//追加条件
            }
        }
        //dump($where_project);
        //dump($flow_process_ids);
        if (($role_id != '1') && ($role_id != '8')) { //除开立项单位 因为立项单位不在步骤中
            if ($menu == 'dsh') {//当前角色自己审核
                $flow_keshi_id = FlowProcess::getprocessIdByuid($uid, $process_name)->id;
                $map = [
                    ['wf_run.run_flow_process', '=', $flow_keshi_id],
                    ['wf_run.status', '=', 0]
                ];
            } elseif ($menu == 'dfms') {//待分管副秘书长审核项目、
                $flow_keshi_id = FlowProcess::getprocessIdByname($uid, $process_name,'副秘书长');
                $map = [
                    ['wf_run.run_flow_process', '=', $flow_keshi_id],
                    ['wf_run.status', '=', 0]
                ];
            } elseif ($menu == 'dfg') {//待分管副市长审核项目
                $flow_keshi_id = FlowProcess::getprocessIdByname($uid, $process_name,'分管副市长');
                $map = [
                    ['wf_run.run_flow_process', '=', $flow_keshi_id],
                    ['wf_run.status', '=', 0]
                ];
            } elseif ($menu == 'dcw') {//待常务副市长审核项目
                $flow_keshi_id = FlowProcess::getprocessIdByname($uid, $process_name,'常务副市长');
                $map = [
                    ['wf_run.run_flow_process', '=', $flow_keshi_id],
                    ['wf_run.status', '=', 0]
                ];
            } elseif ($menu == 'dsz') {//待市长审核项目
                $flow_keshi_id = FlowProcess::getprocessIdByname($uid, $process_name,'市长');
                $map = [
                    ['wf_run.run_flow_process', '=', $flow_keshi_id],
                    ['wf_run.status', '=', 0]
                ];
            } elseif ($menu == 'shz') {//审核中
                $flow_process_ids = FlowProcess::getprocessIdsByflow_id($uid, $process_name);
                $map = [
                    ['wf_run.status', '=', 0]
                ];
            } elseif ($menu == 'bh') { //我驳回项目
                $map = [
                    ['wf_run_log.btn', '=', 'Back'],
                    ['wf_run_log.uid', '=', $uid]
                ];
                $where_project_push =['status_flow', '=', '-1'];
                array_push($where_project,$where_project_push);//追加条件
            } elseif ($menu == 'jsz') { //建设中
                $flow_process_ids = FlowProcess::getprocessIdsByflow_id($uid, $process_name);
                $where_project_push =['status_flow', '=', '2'];
                array_push($where_project,$where_project_push);//追加条件
            }
        }
        //dd($map);

//        DB::connection()->enableQueryLog();
        // 构建子查询
        $query_build = RunProcess::leftJoin('wf_run', 'wf_run.id', '=', 'wf_run_process.run_id')
            ->leftjoin('wf_run_log', 'wf_run_log.from_id', '=', 'wf_run.from_id')
            ->groupBy('wf_run_process.run_id')
            ->where($map)
            ->when($flow_process_ids, function ($query) use ($flow_process_ids) {
                //dd($flow_process_ids);
                return $query->whereIn('wf_run_process.run_flow_process', $flow_process_ids);// 查询条件有区别
            })
            ->where('wf_run.from_table', '=', $wf_type)//关联业务表
            ->select('wf_run.from_id');
        //子查询调用方法
        $result = Project::whereIn('id', function ($query) use ($query_build) {
            $query->from(DB::raw("({$query_build->toSql()}) as pid"));
            $query->mergeBindings($query_build->getQuery());
        })->where($where_project)
            ->filter($request->all())//查询过滤
            ->when(!$request->has('is_paginate'), function ($query) use ($paginate) {
                //默认分页 资源集合进行数据转换
                return new ProjectCollection($query->paginate($paginate));
            }, function ($query) {
                //只有存在 is_paginate 参数 才不分页
                return $query->get();
            });
//        dd(DB::getQueryLog());
        return $result;
    }

    //后台验证
    public function wfCheck(Request $request){
        $role = $request->input('role_id') ?? 1;//测试接口用
        $uid = $request->input('uid') ?? 1; //测试接口用

        $data['flowinfo'] = Workflow::workflowInfo($request->input('pid'), $request->input('wf_type'), ['uid' => $uid, 'role' => $role]);
        $message = Workflow::workflowCheck($data['flowinfo'], $uid, $role);//后台验证
        return $message;
    }
    //项目审核操作
    public function checkProject(Request $request)
    {
        $role = $request->input('role_id') ?? 1;//测试接口用
        $wf_backflow = $request->input('wf_backflow') ?? '';//模拟退回到科室 2 flow_process ID
        $check_con = $request->input('check_con') ?? '通过'; //审核/驳回意见
        $uid = $request->input('uid') ?? 1; //测试接口用
        $pro_info = Project::find($request->input('pid'));
        $log_time = $request->input('log_time') ?? ''; //迁移数据

        $wf_process = ProcessRepo::getWorkflowProcess($pro_info->wf_id); //当前流程第一个步骤

        $info = ['wf_title' => $request->input('wf_title'), 'wf_fid' => $request->input('pid'), 'wf_type' => $request->input('wf_type')];
        $info = json_decode(json_encode($info, true));
        $data['info'] = $info;
        $data['flowinfo'] = Workflow::workflowInfo($request->input('pid'), $request->input('wf_type'), ['uid' => $uid, 'role' => $role]);
        $data['flowinfo'] = json_decode(json_encode($data['flowinfo'], true));

        $data['info'] = objectToArray($data['info']); //转数组
        $data['flowinfo'] = objectToArray($data['flowinfo']);//转数组
        //dd($data['flowinfo']);
        if ($data['flowinfo']['wf_mode'] != '2') {
            //dd($data['flowinfo']);
            $npid = $data['flowinfo']['nexprocess']['id']; //下一步骤id
        } else {
            $npid = $data['flowinfo']['process']['process_to'];
        }

        if ($wf_backflow != '') {
            $submit_to_save = 'back';
            $backflow_id = 0; //驳回步骤id：$wf_process->id 退回立项单位 0
        }elseif($check_con =='default'){
            $submit_to_save = 'default'; //系统默认
            $backflow_id = '';
        }else {
            $submit_to_save = 'ok';
            $backflow_id = '';
        }

        $save = [
            "wf_title" => $data['info']['wf_title'],
            "wf_fid" => $data['info']['wf_fid'],
            "wf_type" => $data['info']['wf_type'],
            "flow_id" => $data['flowinfo']['flow_id'],
            "flow_process" => $data['flowinfo']['flow_process'],
            "run_id" => $data['flowinfo']['run_id'],
            "run_process" => $data['flowinfo']['run_process'],
            "npid" => $npid ?? "",
            "wf_mode" => $data['flowinfo']['wf_mode'],
            "sup" => "",
            "check_con" => $check_con,
            "wf_backflow" => $backflow_id,
            "btodo" => "",
            "wf_singflow" => "",
            "submit_to_save" => $submit_to_save, //
            "art" => "",
            "sing_st" => $data['flowinfo']['sing_st'],
            'log_time'=>$log_time
        ];

        return Workflow::workdoaction($save, $uid);//获取下一步骤信息 根据按钮值 做提交 或者回退处理
    }

    //根据前端协办格式转成 旧数据格式
    public function dataFormatXieban($data){
        $info =[];
        if(!empty($data)){
            //拼接数据 协办 按照旧数据格式
            $xieban ='';
            $xie_fuzhe = '';
            foreach ($data as $k=>$item){
                $xieban .=$item['cooprateCorp'].'|';
                $xie_fuzhe .=$item['cooprateMan'].'|';
            }
            $info['xieban'] = rtrim($xieban,'|');
            $info['xie_fuze'] = rtrim($xie_fuzhe,'|');
        }
        return $info;
    }

    //自定义节点数据 转化成数组格式 方便入库
    public function arrayToplan($plan_info,$project,$is_id=0){
        $plan_form = [];
        $plan_form_create = [];
        foreach ($plan_info as $k => $item_plan) {
            foreach ($item_plan as $p => $item) {
                if($is_id ==1){
                    $plan_form[$k][$p]['pid'] = $project->id;
                }
                if(isset($item['customid'])){
                    $plan_form[$k][$p]['id'] = $item['customid'];//节点自增ID
                }
                $plan_form[$k][$p]['p_name'] = $k . '_jc_p';
                $plan_form[$k][$p]['p_value'] = $item['_jc_p']['value']; //1级节点
                $plan_form[$k][$p]['m_name'] = $k . '_jc_c';
                $plan_form[$k][$p]['m_value'] = $item['_jc_c']['value']; //2级节点
                $plan_form[$k][$p]['m_zrdw']= implode(',',$item['select']['value']); //组成字符串
                $p_month_str = '';//拼接月份字符串
                for ($i = 1; $i <= 12; $i++) { //月份
                    if ($item['month' . $i]['value'] == true) {
                        $plan_form[$k][$p]['content' . $i] = $item['month' . $i]['content']; //获取节点内容
                        $p_month_str .= $i . ',';//拼接月份
                    }else{
                        $plan_form[$k][$p]['content' . $i] ='';
                    }
                }
                $plan_form[$k][$p]['p_year'] = $project->year;
                $plan_form[$k][$p]['p_month'] = rtrim($p_month_str, ',') ?? '';

                $plan_form_create[] = $plan_form[$k][$p];
            }
        }
        return $plan_form_create;
    }

    //预留编号 +15位 规则编号
    public function getProjectNum($project_form,$user){
        //先查询是否有记录
        $info = Project::where('id','<>',0)->orderBy('id','desc')->first();
        //dd($info['id']);
        if ($info) {
            $number_id = str_pad($info['id'] + 1, 4, "0", STR_PAD_LEFT); //编号 补位 4位
        } else {
            $number_id = '0001'; //编号
        }
        $area =$user->area_info->code ?? 0;//地区编码
        $corp =$user->unit->code ?? 0;//单位编码
        //重点级别
        switch ($project_form['pro_type']) {
            case 1:
                $pro_type = 'a';
                break;
            case 2:
                $pro_type = 'b';
                break;
            case 3:
                $pro_type = 'c';
                break;
            default:
                $pro_type = 'a';
                break;
        }
        $pro_type = strtoupper($pro_type); //转大写
        //新建-续建
        if ($project_form['is_new'] == 0) {
            //新建
            $new = 'n';
        } else {
            $new = 'c';
        }
        $new = strtoupper($new); //转大写
        //分管市领导编号
        $fen_uid = 1;
        if ($project_form['fen_uid'] == 16) {
            $fen_uid = 1;
        } else if ($project_form['fen_uid'] == 17) {
            $fen_uid = 2;
        } else if ($project_form['fen_uid'] == 18) {
            $fen_uid = 3;
        } else if ($project_form['fen_uid'] == 19) {
            $fen_uid = 4;
        } else if ($project_form['fen_uid'] == 20) {
            $fen_uid = 5;
        } else if ($project_form['fen_uid'] == 21) {
            $fen_uid = 6;
        }
        //项目性质
        $ptype = $project_form['type'];
        //总投资规模
        $amount = $project_form['amount'];
        if ($amount > 100000) {
            $amount_type = 1; //大于10亿 10亿 = 100000万元
        } else if (50000 < $amount && $amount <= 100000) {
            $amount_type = 2; //5亿到10亿
        } else if (10000 < $amount && $amount <= 50000) {
            $amount_type = 3; //1亿到5亿
        } else if (5000 < $amount && $amount <= 10000) {
            $amount_type = 4; //5千万到1亿
        } else {
            $amount_type = 5; //5千万以下
        }
        $year = substr(date('Y', time()), 2); //截取前面2位
        return $area . $corp . $pro_type . $new . $fen_uid . $ptype . $amount_type . $year . $number_id;
    }

    //获取项目标准模板表 引导页面第二步
    public function getPlaninfo($type){
        $user = Auth::guard('api')->user();
        $units_id = $user->units_id ?? 1;
        $units_alias_name = $user->unit->alias_name ?? '';
        $units_name = $user->unit->name ??'';
        $array =[];
        $response= [];
        $plan = \App\Models\ProjectPlanTemplate::where('type', $type)
            ->select('p_name', 'p_value', 'm_name', 'm_value')// 'p_name' , 'm_name'
            ->orderByRaw('p_name,m_name asc')
            //->pluck('p_name')
            ->get()
            ->toArray();

        foreach ($plan as $k => $val) {
            $array[$val['p_name']][] = $val;
        }
        $array = array_values($array);
        //拼接成前端要的格式
        foreach ($array as $k => $item) {
            foreach ($item as $k2 => $item2) {
                $response['plan'][$k][$k2]['_jc_p']['value'] = $item2['p_value']; //1级节点
                $response['plan'][$k][$k2]['_jc_c']['value'] = $item2['m_value']; //2级节点
                $response['plan'][$k][$k2]['_jc_p']['type'] = 'input';
                $response['plan'][$k][$k2]['_jc_c']['type'] = 'input';

                for ($i = 1; $i <= 12; $i++) { //月份
                    $response['plan'][$k][$k2]['month' . $i]['value'] = '';
                    $response['plan'][$k][$k2]['month' . $i]['type'] = 'checkbox';
                    $response['plan'][$k][$k2]['month' . $i]['content'] = '';
                }
                //责任单位
                $response['plan'][$k][$k2]['select']['value'] = '';
                $response['plan'][$k][$k2]['select']['type'] ='select';
                //单位列表
                //$response['units'] = \App\Models\Unit::getAll();
                $response['nowtime'] = date('Y-m-d', time());
                //立项单位
                $response['lx_unit']['units_id'] = $units_id;
                $response['lx_unit']['units_alias_name'] = $units_alias_name;
                $response['lx_unit']['units_name'] = $units_name;
            }
        }
        return $response;
    }

    //项目详细页 转换 plancustom
    public function getPlancustom($plan,$xieban=0){
        $array =[];
        $response= [];
        //dd($plan);
        if(!empty($plan)){
            foreach ($plan as $k => $val) {
                $array[$val['p_name']][] = $val;
            }
            $array = array_values($array);

            //拼接成前端要的格式
            $m_zrdw = [];
            foreach ($array as $k => $item) {
                foreach ($item as $k2 => $item2) {
                    $response[$k][$k2]['_jc_p']['value'] = $item2['p_value']; //1级节点
                    $response[$k][$k2]['_jc_c']['value'] = $item2['m_value']; //2级节点
                    $response[$k][$k2]['_jc_p']['type'] = 'input';
                    $response[$k][$k2]['_jc_c']['type'] = 'input';
                    if($xieban)
                    {
                        //计算当前节点√ 总数   协办事项使用
                        $g_count = count(explode(',', trim($item2['p_month'], ',')));
                        $progress_list = Progress::select('id','p_status','month','y_time','custom_id','pid','p_year')
                            ->whereRaw('custom_id =' . $item2['id'] . ' and pid =' . $item2['pid']  . ' and p_year=' . $item2['p_year'] . '')
                            ->orderByRaw('p_time desc');
                        $t_score = 0; //当前节点进度 总和
                        $p_score = 0;
                        foreach ($progress_list as $k_p => $vp) {
                            if ($vp['p_status'] == '0') {
                                $p_score = 0;
                            } elseif ($vp['p_status'] == '1') {
                                $p_score = 25;
                            } elseif ($vp['p_status'] == '2') {
                                $p_score = 50;
                            } elseif ($vp['p_status'] == '3') {
                                $p_score = 75;
                            } elseif ($vp['p_status'] == '4') {
                                $p_score = 100;
                            }
                            $t_score += $p_score;
                        }
                        $total = $t_score / (intval($g_count) * 100) * 100;
                        $response[$k][$k2]['fenshu'] = round($total, 1);
                        $response[$k][$k2]['status'] = 'pro1';
                        //协办事项使用
                    }
                    for ($i = 1; $i <= 12; $i++) { //月份
                        $response[$k][$k2]['month' . $i]['customid'] = $item2['id']; //节点ID

                        if(($item2['content'.$i] !='') || ($item2['content'.$i] !=null)){
                            $response[$k][$k2]['month' . $i]['value'] = true;
                        }
                        else{
                            $response[$k][$k2]['month' . $i]['value'] = '';
                        }
                        $response[$k][$k2]['month' . $i]['type'] = 'checkbox';
                        $response[$k][$k2]['month' . $i]['content'] = $item2['content'.$i];
                        /** 获取进度内容 **/
                        $progress_info = Progress::
                            select('id','p_status','month','y_time','custom_id','pid','p_year')
                            ->whereRaw('custom_id =' . $item2['id'] . ' and pid =' . $item2['pid'] . ' and month=' . $i . ' and p_year=' . $item2['p_year'] . '')
                            ->orderByRaw('p_time desc')
                            ->first(); //取最新一条数据 id desc,
                        //echo M()->_sql();
                        //$i_status = 'status';
                        $i_status_class = 'status_class';
                        $i_status_title = 'status_title';
                        $i_status_span = 'status_span';
                        $p_status = isset($progress_info['p_status']) ? $progress_info['p_status'] : '';
                        $status = isset($progress_info['status']) ? $progress_info['status'] : '';
                        $y_time = isset($progress_info['y_time']) ? $progress_info['y_time'] : '';
                        $p_class = '';
                        $p_title = '未填';
                        $p_span = '√';
                        if ($p_status == '0') {
                            $p_class = 'pro1';
                            $p_title = '无进度';
                            $p_span = '0%';
                            if ($y_time > 0 && $status == 6) {
                                //提前完成
//                            $p_class = 'pro3';
//                            $p_title = '提前完成';
                            } elseif ($y_time < 0 && $y_time > -30) {
                                //逾期小于 30天
                                $p_class = 'pro2';
                                $p_title = '逾期';
                            } elseif ($y_time <= -30 && $y_time > -60) {
                                //逾期 30天
                                $p_class = 'pro3';
                                $p_title = '进展缓慢';
                            } elseif ($y_time <= -60) {
                                //逾期 60天
                                $p_class = 'pro4';
                                $p_title = '严重滞后';
                            }
                        } elseif ($p_status == '1') {
                            $p_class = 'pro1';
                            $p_title = '部分进度25%';
                            $p_span = '25%';
                            if ($y_time > 0 && $status == 6) {
                                //提前完成
//                            $p_class = 'pro3';
//                            $p_title = '提前完成';
                            } elseif ($y_time < 0 && $y_time > -30) {
                                //逾期小于 30天
                                $p_class = 'pro2';
                                $p_title = '逾期';
                            } elseif ($y_time <= -30 && $y_time > -60) {
                                //逾期 30天
                                $p_class = 'pro3';
                                $p_title = '进展缓慢';
                            } elseif ($y_time <= -60) {
                                //逾期 60天
                                $p_class = 'pro4';
                                $p_title = '严重滞后';
                            }
                        } elseif ($p_status == '2') {
                            $p_class = 'pro1';
                            $p_title = '部分进度50%';
                            $p_span = '50%';
                            if ($y_time > 0 && $status == 6) {
                                //提前完成
//                            $p_class = 'pro3';
//                            $p_title = '提前完成';
                            } elseif ($y_time < 0 && $y_time > -30) {
                                //逾期小于 30天
                                $p_class = 'pro2';
                                $p_title = '逾期';
                            } elseif ($y_time <= -30 && $y_time > -60) {
                                //逾期 30天
                                $p_class = 'pro3';
                                $p_title = '进展缓慢';
                            } elseif ($y_time <= -60) {
                                //逾期 60天
                                $p_class = 'pro4';
                                $p_title = '严重滞后';
                            }
                        } elseif ($p_status == '3') {
                            $p_class = 'pro1';
                            $p_title = '部分进度75%';
                            $p_span = '75%';
                            if ($y_time > 0 && $status == 6) {
                                //提前完成
//                            $p_class = 'pro3';
//                            $p_title = '提前完成';
                            } elseif ($y_time < 0 && $y_time > -30) {
                                //逾期小于 30天
                                $p_class = 'pro2';
                                $p_title = '逾期';
                            } elseif ($y_time <= -30 && $y_time > -60) {
                                //逾期 30天
                                $p_class = 'pro3';
                                $p_title = '进展缓慢';
                            } elseif ($y_time <= -60) {
                                //逾期 60天
                                $p_class = 'pro4';
                                $p_title = '严重滞后';
                            }
                        } elseif ($p_status == '4') {
                            //正常完成
                            $p_class = 'pro1';
                            $p_title = '已完成';
                            $p_span = '100%';
                            if ($status == 6) {
                                //提前完成
//                            $p_class = 'pro3';
//                            $p_title = '提前完成';
                            }
                        } elseif ($p_status == '5') {
                            //echo 11;
                            $p_class = '';
                            $p_title = '未填';
                            $p_span = '√';
                            //如果是系统扫描 取上一条记录的 p_status
                            $xt_info = Progress::
                                select('id','p_status','month','y_time')
                                ->whereRaw('custom_id =' . $progress_info['custom_id'] . ' and pid =' . $progress_info['pid'] . '  and month=' . $progress_info['month'] . ' and p_status !=5')
                                ->orderByRaw('id desc')
                                ->first();
                            //dump($xt_info);
                            if ($y_time > 0 && $status == 6) {
                                //提前完成
//                            $p_class = 'pro3';
//                            $p_title = '提前完成';
                            } elseif ($y_time < 0 && $y_time > -30) {
                                //逾期小于 30天
                                //echo 123;
                                $p_class = 'pro2';
                                $p_title = '逾期';
                                if (!empty($xt_info)) {
                                    if ($xt_info['p_status'] == 0) {
                                        $p_span = '0%';
                                    } elseif ($xt_info['p_status'] == 1) {
                                        $p_span = '25%';
                                    } elseif ($xt_info['p_status'] == 2) {
                                        $p_span = '50%';
                                    } elseif ($xt_info['p_status'] == 3) {
                                        $p_span = '75%';
                                    }
                                }
                            } elseif ($y_time <= -30 && $y_time > -60) {
                                //逾期 30天
                                $p_class = 'pro3';
                                $p_title = '进展缓慢';
                                if (!empty($xt_info)) {
                                    if ($xt_info['p_status'] == 0) {
                                        $p_span = '0%';
                                    } elseif ($xt_info['p_status'] == 1) {
                                        $p_span = '25%';
                                    } elseif ($xt_info['p_status'] == 2) {
                                        $p_span = '50%';
                                    } elseif ($xt_info['p_status'] == 3) {
                                        $p_span = '75%';
                                    }
                                }
                            } elseif ($y_time <= -60) {
                                //逾期 60天
                                $p_class = 'pro4';
                                $p_title = '严重滞后';
                                if (!empty($xt_info)) {
                                    if ($xt_info['p_status'] == 0) {
                                        $p_span = '0%';
                                    } elseif ($xt_info['p_status'] == 1) {
                                        $p_span = '25%';
                                    } elseif ($xt_info['p_status'] == 2) {
                                        $p_span = '50%';
                                    } elseif ($xt_info['p_status'] == 3) {
                                        $p_span = '75%';
                                    }
                                }
                            }
                        }
                        //$custom_dt[$k]['children'][$k_item][$i_status] = $p_status;
                        $response[$k][$k2]['month' . $i][$i_status_class] = $p_class;
                        $response[$k][$k2]['month' . $i][$i_status_title] = $p_title;
                        $response[$k][$k2]['month' . $i][$i_status_span] = $p_span;
                        /** 获取进度内容 **/
                    }
                    $m_zrdw=explode(',',$item2['m_zrdw']);
                    //责任单位
                    $response[$k][$k2]['select']['value'] = $m_zrdw;
                    $response[$k][$k2]['select']['name'] = \App\Models\Unit::getNames($item2['m_zrdw']);
                    $response[$k][$k2]['select']['type'] ='select';
                }
            }
        }
        return $response;
    }

    //项目详细页
    public function getProjectInfo($pid){
        $user = Auth::guard('api')->user();
        $userid = $user->id;
        $project_info =[];
        $flow_project = Project::withCount([
            'user' => function ($query) use($pid){
                $query->where('project_id', $pid);
            }])->find($pid); //关注项目人数

        $flow_project_user = Project::withCount([
            'user' => function ($query) use($pid,$userid){
                $query->where('project_id', $pid);
                $query->where('user_id', $userid);
            }])->find($pid); //当前用户关注项目人数

        $project_info = new ProjectResource(
            Project::with(['runlog' => function ($query) use ($pid) {
                //plancustom
                //->hide(['form.id'])
            }])->find($pid)
        );
        //$project_info = collect($project_info)->toArray();
        //$plan =$project_info['plancustom']->toArray();//项目节点
        //$project_info['plancustom'] =$this->getPlancustom($plan);
        $project_info['flow_count'] = $flow_project->user_count ?? 0;
        $project_info['is_flow'] = false; //当前用户是否关注过此项目
        if($flow_project_user){
            if($flow_project_user->user_count > 0){
                $project_info['is_flow'] = true;
            }
        }
        //dd($project_info);
        return $project_info;
    }

    //获取项目节点内容 本来是节点是放在项目详细页 返回数据太慢 拆开接口
    public function getPlans($pid){
        $project_info = Project::with('plancustom')->find($pid);
        $plancustom =$this->getPlancustom($project_info->plancustom);
        //dd($plancustom);
        return $plancustom;
    }
}