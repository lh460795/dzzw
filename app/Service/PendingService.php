<?php

namespace App\Service;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Supervise;
use App\Models\ProjectPlanCustom;
use App\Models\Review;
use App\Models\Role;
/**
 * Class PendingService
 * @package App\Service
 * 待办事项公共类
 */

class PendingService {

    //获取当前角色拥有的待办标签
    public function getMarkData() {
        $id = 16;
        $roles = User::find($id)->roles()->first();
        if (collect($roles)->isNotEmpty()) {
            $roleId = $roles->id;
            $tags = $roles->pending()->select('pendings.id','type as title')->get()->toArray();
        } else {
            $roleId = [];
        }




        //$roleId = 21;



        switch ($roleId) {
            case 21:
                $data = $this->projectOperator();
                $result = $this->composeProject($tags, $data);
                break;
            case 29:
                $data = $this->unitsLeader();
                $result = $this->composeOperator($tags, $data);
                break;
            case 22:
                $data = $this->operator();
                $result = $this->composeOperator($tags, $data);
                break;
            case 23:
                $data = $this->secretary();
                $result = $this->composeOperator($tags, $data);
                break;
            case 25:
                $data = $this->wuhua();
                $result = $this->composeWuhua($tags, $data);
                break;
            case 24:
                $data = $this->vicemayor();
                $result = $this->composeMayor($tags, $data);
                break;
            case 26:
                $data = $this->routineMayor();
                $result = $this->composeMayor($tags, $data);
                break;
            case 27:
                $data = $this->mayor();
                $result = $this->composeMayor($tags, $data);
                break;
            default:
                return [];
                break;
        }

        return $result;
    }

    public function composeProject( $tags, array $data) {
        foreach ($tags as $k=>$v) {
            if ($tags[$k]['title'] ==  '在建项目') {
                $tags[$k]['count'] = $data[0];
            }
            if ($tags[$k]['title'] ==  '审核中项目') {
                $tags[$k]['count'] = $data[1];
            }
            if ($tags[$k]['title'] ==  '被督办项目') {
                $tags[$k]['count'] = $data[2];
            }
            if ($tags[$k]['title'] ==  '协办项目') {
                $tags[$k]['count'] = $data[3];
            }
        }

        return $tags;
    }

    public function composeOperator( $tags, array $data) {
        foreach ($tags as $k=>$v) {
            if ($tags[$k]['title'] ==  '在建项目') {
                $tags[$k]['count'] = $data[0];
            }
            if ($tags[$k]['title'] ==  '关注的项目') {
                $tags[$k]['count'] = $data[1];
            }
            if ($tags[$k]['title'] ==  '纳统项目') {
                $tags[$k]['count'] = $data[2];
            }
        }

        return $tags;
    }

    public function composeWuhua( $tags, array $data) {
        foreach ($tags as $k=>$v) {
            if ($tags[$k]['title'] ==  '在建项目') {
                $tags[$k]['count'] = $data[0];
            }
            if ($tags[$k]['title'] ==  '关注的项目') {
                $tags[$k]['count'] = $data[1];
            }
            if ($tags[$k]['title'] ==  '审核中项目') {
                $tags[$k]['count'] = $data[2];
            }
            if ($tags[$k]['title'] ==  '被督办项目') {
                $tags[$k]['count'] = $data[3];
            }
        }

        return $tags;
    }

    public function composeMayor( $tags, array $data) {
        foreach ($tags as $k=>$v) {
            if ($tags[$k]['title'] ==  '在建项目') {
                $tags[$k]['count'] = $data[0];
            }
            if ($tags[$k]['title'] ==  '关注的项目') {
                $tags[$k]['count'] = $data[1];
            }
            if ($tags[$k]['title'] ==  '纳统项目') {
                $tags[$k]['count'] = $data[2];
            }
            if ($tags[$k]['title'] ==  '述评动态') {
                $tags[$k]['count'] = $data[3];
            }
        }

        return $tags;
    }

    //获取立项单位操作员统计数据
    public function projectOperator() {
        //$id = \Auth::guard('api')->id();
        //$unit_id =  \Auth::guard('api')->user()
        $id = 1;
        $units_id = 1;
        $build = Project::where('uid', $id)
                        ->whereIn('pro_status', [
                            Project::PROJECT_NORMAL,
                            Project::PROJECT_DELAY,
                            Project::PROJECT_SLOW,
                            Project::PROJECT_OVERDUE
                        ])->count();

        $audit = Project::where('uid', $id)
                         ->where('status_flow', '!=', 2)
                         ->count();

        $supervise = Supervise::where('touid', $id)->count();

        $jointly = ProjectPlanCustom::whereRaw( " find_in_set({$units_id}, `m_zrdw`)" )
                                    ->distinct()
                                    ->groupBy('pid')->count();

        return [$build, $audit, $supervise, $jointly];

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
    public function operator() {
        $user_id = 10;
        //在建项目
        $build = Project::
            where('pro_status', Project::PROJECT_OVERDUE)
            ->where('uid', $user_id)
            ->where('status_flow', 2)
            ->count();

        $attention = User::with(['project' =>function ($query) use ($user_id) {
            $query->whereIn('pro_status', [
                Project::PROJECT_NORMAL,
                Project::PROJECT_DELAY,
                Project::PROJECT_SLOW,
                Project::PROJECT_OVERDUE
            ]);
        }])
        ->where('id', $user_id)
        ->count();

        //纳统
        $natong = Project::with(['natong'=>function ($query){
            $query->where('is_incor', 2);
        }])
            ->where('uid', $user_id)
            ->where('status_flow', 2)
            ->count();

        return [$build, $attention, $natong];
    }

    //获取副秘书长统计数据
    public function secretary() {
        $user_id = 11;
        //在建项目
        $build = Project::
            where('pro_status', Project::PROJECT_OVERDUE)
            ->where('uid', $user_id)
            ->where('status_flow', 2)
            ->count();

        $attention = User::with(['project' =>function ($query) use ($user_id) {
            $query->whereIn('pro_status', [
                Project::PROJECT_NORMAL,
                Project::PROJECT_DELAY,
                Project::PROJECT_SLOW,
                Project::PROJECT_OVERDUE
            ]);
        }])
            ->where('id', $user_id)
            ->count();

        //纳统
        $natong = Project::with(['natong'=>function ($query){
            $query->where('is_incor', 2);
        }])
            ->where('uid', $user_id)
            ->where('status_flow', 2)
            ->count();

        return [$build, $attention, $natong];
    }

    //获取五化办统计数据
    public function wuhua() {
        $id = 1;
        $units_id = 1;
        $user_id = 13;
        //在建项目
        $build = Project::whereIn('pro_status', [
                 Project::PROJECT_NORMAL,
                 Project::PROJECT_DELAY,
                 Project::PROJECT_SLOW,
                 Project::PROJECT_OVERDUE
            ])
            ->where('status_flow', '!=', 2)
            ->count();

        //关注的项目
        $attention = User::with(['project' =>function ($query)  {
            $query->whereIn('pro_status', [
                Project::PROJECT_NORMAL,
                Project::PROJECT_DELAY,
                Project::PROJECT_SLOW,
                Project::PROJECT_OVERDUE
            ]);
        }])
        ->where('id', $user_id)
        ->count();

        //审核中的项目
        $audit = Project::where('status_flow', '!=', 2)->count();
        //被督办的项目
        $supervise = Supervise::groupBy('pid')->distinct()->count();


        return [$build, $attention, $audit, $supervise];
    }

    //获取分管副市长统计数据
    public function vicemayor() {

        $user_id = 14;
        //在建项目
        $build = Project::
            where('pro_status', Project::PROJECT_OVERDUE)
            ->where('uid', $user_id)
            ->where('status_flow', 2)
            ->count();

        //关注的项目
        $attention = User::with(['project' =>function ($query)  {
            $query->whereIn('pro_status', [
                Project::PROJECT_NORMAL,
                Project::PROJECT_DELAY,
                Project::PROJECT_SLOW,
                Project::PROJECT_OVERDUE
            ]);
        }])
        ->where('id', $user_id)
        ->count();

        //当前用户分管的纳统项目
        $natong = Project::with(['natong'=>function ($query){
            $query->where('is_incor', 2);
        }])
            ->where('uid', $user_id)
            ->where('fen_uid', $user_id)
            ->count();

        //被督办的项目
        $review = Review::where('user_id', $user_id)->distinct()->count();


        return [$build, $attention, $natong, $review];
    }

    //获取常务副市长统计数据
    public function routineMayor() {
        $user_id = 15;
        //在建项目
        $build = Project::
        where('pro_status', Project::PROJECT_OVERDUE)
            ->where('uid', $user_id)
            ->where('status_flow', 2)
            ->count();

        //关注的项目
        $attention = User::with(['project' =>function ($query)  {
            $query->whereIn('pro_status', [
                Project::PROJECT_NORMAL,
                Project::PROJECT_DELAY,
                Project::PROJECT_SLOW,
                Project::PROJECT_OVERDUE
            ]);
        }])
            ->where('id', $user_id)
            ->count();

        //全市项目的纳统项目
        $natong = Project::with(['natong'=>function ($query){
            $query->where('is_incor', 2);
        }])
            //->where('uid', $user_id)
            //->where('status_flow', 2)
            ->count();

        //述评数量
        $review = Review::where('user_id', $user_id)
            //->distinct()
            ->count();


        return [$build, $attention, $natong, $review];
    }

    //获取市长统计数据
    public function mayor() {
        $user_id = 16;
        //在建项目
        $build = Project::
        where('pro_status', Project::PROJECT_OVERDUE)
            ->where('uid', $user_id)
            ->where('status_flow', 2)
            ->count();

        //关注的项目
        $attention = User::with(['project' =>function ($query)  {
            $query->whereIn('pro_status', [
                Project::PROJECT_NORMAL,
                Project::PROJECT_DELAY,
                Project::PROJECT_SLOW,
                Project::PROJECT_OVERDUE
            ]);
        }])
            ->where('id', $user_id)
            ->count();

        //纳统项目
        $natong = Project::with(['natong'=>function ($query){
            $query->where('is_incor', 2);
        }])
            //->where('status_flow', 2)
            ->count();


        $review = Review::where('user_id', $user_id)
            //->distinct()
            ->count();

        return [$build, $attention, $natong, $review];
    }

    //判断当前用户级别
    public function hasPermission($duty_name = '五化办') {
        $roles = User::find(\Auth::guard('api')->id())->roles()->first();
        if (collect($roles)->isNotEmpty()) {
            $roleId = $roles->level_id;
        }

        $wuhua = Role::where('name', $duty_name)->value('level_id');

        if ( $roleId >= $wuhua) {
            return true;
        }

        return false;
    }

}
