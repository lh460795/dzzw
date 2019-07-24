<?php

namespace App\Service;
use App\Models\SuperviseReply;
use App\Models\User;
use App\Work\Model\Run;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Supervise;
use App\Models\ProjectPlanCustom;
use App\Models\Review;
use App\Work\Model\FlowProcess;
use App\Work\Model\RunProcess;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Class PendingService
 * @package App\Service
 * 待办事项公共类
 */

class TodoService {

    //获取当前角色拥有的待办标签
    public function getMarkData() {
        $id = 1;
        $roles = User::find($id)->roles()->first();
        if (collect($roles)->isNotEmpty()) {
            $roleId = $roles->id;
        }

        $roleId = 21;

        $tags = $roles->todo()->select('role_todos.id as todoId','todo_type as todoName')->get()->toArray();

        //$roleId = 21;



        switch ($roleId) {
            case Role::projectOperator:
                $data = $this->projectOperator();
                $result = $this->composeProject($tags, $data);
                break;
            case Role::unitsLeader:
                $data = $this->unitsLeader();
                $result = $this->composeUnitLeader($tags, $data);
                break;
            case Role::Operator:
                $data = $this->operator($roleId, $id);
                $result = $this->composeOperator($tags, $data);
                break;
            case Role::Secretary:
                $data = $this->secretary($roleId, $id);
                $result = $this->composeOperator($tags, $data);
                break;
            case Role::wuhua:
                $data = $this->wuhua($roleId, $id);
                $result = $this->composeWuhua($tags, $data);
                break;
            case Role::vicemayor:
                $data = $this->vicemayor($roleId, $id);
                $result = $this->composeOperator($tags, $data);
                break;
            case Role::routineMayor:
                $data = $this->routineMayor($roleId, $id);
                $result = $this->composeOperator($tags, $data);
                break;
            case Role::mayor:
                $data = $this->mayor($roleId, $id);
                $result = $this->composeOperator($tags, $data);
                break;
            default:
                return [];
                break;
        }

        return $result;
    }

    public function composeProject( $tags, array $data) {
        foreach ($tags as $k=>$v) {
            if ($tags[$k]['todoName'] ==  '进度填报') {
                $tags[$k]['todoList'] = $data[0];
            }
            if ($tags[$k]['todoName'] ==  '督办函回复') {
                $tags[$k]['todoList'] = $data[1];
            }

        }

        return $tags;
    }

    public function composeUnitLeader( $tags, array $data) {
        foreach ($tags as $k=>$v) {
            if ($tags[$k]['todoName'] ==  '进度填报') {
                $tags[$k]['todoList'] = $data[0];
            }
            if ($tags[$k]['todoName'] ==  '进度审核') {
                $tags[$k]['todoList'] = $data[1];
            }

        }

        return $tags;
    }

    public function composeOperator( $tags, array $data) {
        foreach ($tags as $k=>$v) {
            if ($tags[$k]['todoName'] ==  '立项审核') {
                $tags[$k]['todoList'] = $data[0];
            }
            if ($tags[$k]['todoName'] ==  '出库审核') {
                $tags[$k]['todoList'] = $data[1];
            }
            if ($tags[$k]['todoName'] ==  '项目停建审核') {
                $tags[$k]['todoList'] = $data[2];
            }
        }

        return $tags;
    }

    public function composeWuhua( $tags, array $data) {
        foreach ($tags as $k=>$v) {
            if ($tags[$k]['todoName'] ==  '立项审核') {
                $tags[$k]['todoList'] = $data[0];
            }
            if ($tags[$k]['todoName'] ==  '出库审核') {
                $tags[$k]['todoList'] = $data[1];
            }
            if ($tags[$k]['todoName'] ==  '确认回函') {
                $tags[$k]['todoList'] = $data[2];
            }
            if ($tags[$k]['todoName'] ==  '项目修改审核') {
                $tags[$k]['todoList'] = $data[2];
            }
            if ($tags[$k]['todoName'] ==  '项目停建审核') {
                $tags[$k]['todoList'] = $data[2];
            }
        }

        return $tags;
    }



    //获取立项单位操作员统计数据
    public function projectOperator() {
        //$id = \Auth::guard('api')->id();
        //$unit_id =  \Auth::guard('api')->user()
        $id = 21;

        //进度填报
        $process = [];
        //督办函回复
        $pid = Project::where('uid', $id)
                    ->whereIn('pro_status', [
                        Project::PROJECT_NORMAL,
                        Project::PROJECT_DELAY,
                        Project::PROJECT_SLOW,
                        Project::PROJECT_OVERDUE
                    ])
                    ->select('id')->get()->toArray();
        $pid = array_column($pid, 'id');

        $duban_id = Supervise::whereIn('pid', $pid)
            ->where('touid', $id)
            ->select('id')->get()->toArray();

        $duban_id = array_column($duban_id, 'id');

        $duban = SuperviseReply::whereIn('duban_id', $duban_id)->get();


        return [$process, $duban];

    }

    //获取立项单位负责人统计数据
    public function unitsLeader() {
        //$unit_id =  \Auth::guard('api')->user()
        $units_id = 1;
        $build = Project::whereIn('pro_status', [
                 Project::PROJECT_NORMAL,
                 Project::PROJECT_DELAY,
                 Project::PROJECT_SLOW,
                 Project::PROJECT_OVERDUE
            ])
            ->where('units_id', $units_id)
            ->count();

        $audit = Project::where('status_flow', '!=', 2)
            ->where('units_id', $units_id)
            ->count();
        $user_id = User::where('units_id', $units_id)->select('id')->get()->toArray();
        $user_id = array_column($user_id, 'id');
        $supervise = Supervise::whereIn('touid', $user_id)->count();

        $jointly = ProjectPlanCustom::whereRaw( " find_in_set({$units_id}, `m_zrdw`)" )
            ->distinct()
            ->groupBy('pid')->count();

        return [$build, $audit, $supervise, $jointly];
    }

    //获取业务科室操作员统计数据
    public function operator($role_id, $id) {

        //立项审核，出库审核 项目停建审核
        $build = $this->getProject($role_id, $id);
        $stock = $this->completedsh($role_id, $id);
        $stop = $this->adjustdsh($role_id, $id,2);

        return [$build, $stock, $stop];
    }

    //获取副秘书长统计数据
    public function secretary($role_id, $id) {
        //立项审核，出库审核 项目停建审核
        $build = $this->getProject($role_id, $id);
        $stock = $this->completedsh($role_id, $id);
        $stop = $this->adjustdsh($role_id, $id,2);

        return [$build, $stock, $stop];
    }

    //获取五化办统计数据
    public function wuhua($role_id, $id) {
        $build = $this->getProject($role_id, $id);
        $stock = $this->completedsh($role_id, $id);


        $pid = Project::where('uid', $id)
                 ->whereIn('pro_status', [
                    Project::PROJECT_NORMAL,
                    Project::PROJECT_DELAY,
                    Project::PROJECT_SLOW,
                    Project::PROJECT_OVERDUE
                ])
                ->select('id')->get()->toArray();

        $pid = array_column($pid, 'id');

        $duban_id = Supervise::whereIn('pid', $pid)
            ->where('touid', $id)
            ->select('id')->get()->toArray();

        $duban_id = array_column($duban_id, 'id');

        $confirm = SuperviseReply::whereNotIn('duban_id', $duban_id)->get();

        $modify = $this->adjustdsh($role_id, $id,1);
        $stop = $this->adjustdsh($role_id, $id,2);

        return [$build, $stock, $confirm, $modify, $stop];
    }

    //获取分管副市长统计数据
    public function vicemayor($role_id, $id) {

        //立项审核，出库审核 项目停建审核
        $build = $this->getProject($role_id, $id);
        $stock = $this->completedsh($role_id, $id);
        $stop = $this->adjustdsh($role_id, $id,2);

        return [$build, $stock, $stop];
    }

    //获取常务副市长统计数据
    public function routineMayor($role_id, $id) {
        //立项审核，出库审核 项目停建审核
        $build = $this->getProject($role_id, $id);
        $stock = $this->completedsh($role_id, $id);
        $stop = $this->adjustdsh($role_id, $id,2);

        return [$build, $stock, $stop];
    }

    //获取市长统计数据
    public function mayor($role_id, $id) {
        //立项审核，出库审核 项目停建审核
        $build = $this->getProject($role_id, $id);
        $stock = $this->completedsh($role_id, $id);
        $stop = $this->adjustdsh($role_id, $id,2);

        return [$build, $stock, $stop];
    }

    //获取出库待审核列表
    public function completedsh($role_id, $uid)
    {
        $user = Auth::guard('api')->user(); //登录人uid
        //$role_id = Auth::guard('api')->user()->role_id ??1;
        //$uid = $request->get('uid') ??1;//测试接口用
        //$role_id =$request->get('role_id');  //登录人权限
        if(empty($role_id))
        {
            return [];
        }
        //主表条件查询
        $map = [];//条件查询
        if ($role_id == Role::Operator) { //业务科室
            $process_name = '科室';
        } elseif ($role_id == Role::Secretary) {//副秘书长
            $process_name = '副秘书长';
        } elseif ($role_id == Role::vicemayor) {//分管副市长
            $process_name = '分管副市长';
        } elseif ($role_id == Role::wuhua) { //五化办
            $process_name = '五化办';
        } elseif ($role_id == Role::routineMayor) { //常务副市长
            $process_name = '常务副市长';
        } elseif ($role_id == Role::mayor) { //市长
            $process_name = '市长';
        }
        $flow_keshi_id = FlowProcess::getprocessIdByuid($uid,$process_name)->id;
        $map = [
            ['wf_run.run_flow_process', '=', $flow_keshi_id],
            ['wf_run.status', '=', 0]
        ];
        // 构建子查询
        $query_build = RunProcess::leftJoin('wf_run','wf_run.id','=','wf_run_process.run_id')
            ->leftjoin('wf_run_log','wf_run_log.from_id','=','wf_run.from_id')
            ->leftjoin('complete','complete.id','=','wf_run.from_id')
            ->groupBy('wf_run_process.run_id')
            ->where($map)
            ->where('wf_run.from_table','=','complete') //关联业务表
            ->select('complete.pid');
        //子查询调用方法
        $result = Project::whereIn('id', function($query) use ($query_build){
            $query->from(DB::raw("({$query_build->toSql()}) as pid"));
            $query->mergeBindings($query_build->getQuery());
        })->select('id','pname')->get();
        return $result;
    }

    //获取出库待审核列表
    public function adjustdsh($role_id, $uid, $status)
    {
        $user = Auth::guard('api')->user(); //登录人uid
        //$role_id = Auth::guard('api')->user()->role_id ??1;
        //$uid = $request->get('uid') ??1;//测试接口用
        //$role_id =$request->get('role_id');  //登录人权限
        //$status = $request->status;
        //1 修改 2 tingjian
        if(empty($role_id))
        {
            return [];
        }
        //主表条件查询
        $map = [];//条件查询
        if ($role_id == Role::Operator) { //业务科室
            $process_name = '科室';
        } elseif ($role_id == Role::Secretary) {//副秘书长
            $process_name = '副秘书长';
        } elseif ($role_id == Role::vicemayor) {//分管副市长
            $process_name = '分管副市长';
        } elseif ($role_id == Role::wuhua) { //五化办
            $process_name = '五化办';
        } elseif ($role_id == Role::routineMayor) { //常务副市长
            $process_name = '常务副市长';
        } elseif ($role_id == Role::mayor) { //市长
            $process_name = '市长';
        }
        $flow_keshi_id = FlowProcess::getprocessIdByuid($uid,$process_name)->id;
        $map = [
            ['wf_run.run_flow_process', '=', $flow_keshi_id],
            ['wf_run.status', '=', 0]
        ];
        // 构建子查询
        $query_build = RunProcess::leftJoin('wf_run','wf_run.id','=','wf_run_process.run_id')
            ->leftjoin('wf_run_log','wf_run_log.from_id','=','wf_run.from_id')
            ->leftjoin('adjust','adjust.id','=','wf_run.from_id')
            ->groupBy('wf_run_process.run_id')
            ->where($map)
            ->where('wf_run.from_table','=','adjust') //关联业务表
            ->where('adjust.is_adjust','=',$status)
            ->select('adjust.pid');

        //子查询调用方法
        $result = Project::whereIn('id', function($query) use ($query_build){
            $query->from(DB::raw("({$query_build->toSql()}) as pid"));
            $query->mergeBindings($query_build->getQuery());
        })->select('id','pname')->get();

        return $result;
    }

    public function getProject($role_id, $uid) {

        //$role_id = Auth::guard('api')->user()->role_id ??1;
        $menu =  'dks'; //菜单url
        $wf_type =  'project'; //关联业务表 默认是项目表
        $result = [];
        //主表条件查询
        $where_project = [
            ['is_report', '=', 1]
        ];
        $flow_process_ids = null;
        $map = [];//条件查询
        if ($role_id == Role::projectOperator) { //立项单位
            //通过流程id 及步骤名称 找到步骤ID
            $flow_id = Run::getflow_id($uid, $wf_type)->flow_id;
            $flow_keshi_id = FlowProcess::getprocessId($flow_id, '科室')->id;

            if ($menu == 'dks') { //待科室审核
                $map = [
                    ['wf_run.uid', '=', $uid],
                    ['wf_run.run_flow_process', '=', $flow_keshi_id],
                    ['wf_run.status', '=', 0]
                ];
            } elseif ($menu == 'dld') { //待领导审核
                $map = [
                    ['wf_run.run_flow_process', '<>', $flow_keshi_id],
                    ['wf_run.status', '=', 0]
                ];
            } elseif ($menu == 'bh') { //驳回列表
                $map = [
                    ['wf_run_log.btn', '=', 'Back']
                ];
            } elseif ($menu == 'jsz') { //建设中
                $map = [
                    ['wf_run.uid', '=', $uid],
                    ['wf_run.status', '=', 1]
                ];
            }
        } elseif ($role_id == Role::Operator) { //业务科室
            $process_name = '科室';
        } elseif ($role_id == Role::Secretary) {//副秘书长
            $process_name = '副秘书长';
        } elseif ($role_id == Role::vicemayor) {//分管副市长
            $process_name = '分管副市长';
        } elseif ($role_id == Role::wuhua) { //五化办
            $process_name = '五化办';
        } elseif ($role_id == Role::routineMayor) { //常务副市长
            $process_name = '常务副市长';
        } elseif ($role_id == Role::mayor) { //市长
            $process_name = '市长';
        }

        if ($role_id != Role::projectOperator) { //除开立项单位 因为立项单位不在步骤中
            if ($menu == 'dsh') {//当前角色自己审核
                $flow_keshi_id = FlowProcess::getprocessIdByuid($uid, $process_name)->id;
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
            } elseif ($menu == 'jsz') { //建设中
                $flow_process_ids = FlowProcess::getprocessIdsByflow_id($uid, $process_name);
                $map = [
                    ['wf_run.status', '=', 1]
                ];
            }
        }

        // 构建子查询
        $query_build = RunProcess::leftJoin('wf_run', 'wf_run.id', '=', 'wf_run_process.run_id')
            ->leftjoin('wf_run_log', 'wf_run_log.from_id', '=', 'wf_run.from_id')
            ->groupBy('wf_run_process.run_id')
            ->where($map)
            ->when($flow_process_ids, function ($query) use ($flow_process_ids) {
                return $query->whereIn('wf_run_process.run_flow_process', $flow_process_ids);// 查询条件有区别
            })
            ->where('wf_run.from_table', '=', $wf_type)//关联业务表
            ->select('wf_run.from_id');

        $result = Project::whereIn('id', function ($query) use ($query_build) {
            $query->from(DB::raw("({$query_build->toSql()}) as pid"));
            $query->mergeBindings($query_build->getQuery());
        })->where($where_project)
          ->select('id','pname')->get();

        return $result;
    }
}
