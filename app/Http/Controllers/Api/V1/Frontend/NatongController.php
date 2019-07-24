<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Console\Commands\PlanCustomRun;
use App\Http\Resources\Api\V1\Frontend\NatongCollection;
use App\Http\Resources\Api\V1\Frontend\RunLogCollection;
use App\Http\Controllers\Api\Controller;
use App\Models\Natong;
use App\Models\Progress;
use App\Models\Project;
use App\Models\NatongRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests\Api\NatongRequest;
use Auth;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use App\Models\ProjectPlanCustom;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

class NatongController extends Controller
{


    /**
     * 系统判定项目
     * 1、is_incor 状态是 9、10、11
     * 2、查询条件是建设中的项目
     * 3、not in pro_status = 5
     */
    public $xianshiqu_units_id = ['69', '70', '71', '72', '73', '74', '75'];

    /**这个方法勿动
     * @param Request $request
     * @param Natong $Natong
     * @return mixed 测试filter 过滤器效果
     *
     */
    public function index(Request $request, Natong $Natong)
    {
        $Natong = $Natong
            ->select('id', 'pid', 'is_incor')
            ->filter($request->all())->paginate(20);
        return $this->success($Natong);
    }

    /**
     * 更新项目的字段
     *  pid项目id
     * is_incor这个是纳统的状态
     */
    public function update(NatongRequest $request)
    {
        $res = $request->all();

        $pid = $res['pid'] ?? '0';
        $uid = $res['uid'] ?? '0';
        $is_incor = $res['is_incor'] ?? '0';
        $natong = $res['natong'] ?? '0';
        $now_incor = $res['now_incor'] ?? '0';

        //$now_incor-$is_incor： 0-9-13，未纳统->未纳统， 0-11-14疑似未纳统->未纳统， 1是应统未统，2是已纳统  12是取消纳统
        if ($is_incor == 0) {
            if ($now_incor == 9) {
                $is_incor = 13;
            } else {
                $is_incor = 14;
            }
            $res = Natong::where('pid', '=', $pid)->update(['is_incor' => $is_incor, 'natong_month' => $natong, 'natongstatus_time' => time()]);
            $pro_natong = Project::where('id', $pid)->update(['natongstatus_time' => time()]);

            $natong_jilu = new NatongRecord();
            $natong_jilu->pid = $pid;
            $natong_jilu->natong_status = $is_incor;
            $natong_jilu->uid = $uid;
            $natong_jilu->edit_time = time();
            $bool = $natong_jilu->save();
        }
        if ($is_incor == 1) {
            $res = Natong::where('pid', '=', $pid)->update(['is_incor' => $is_incor, 'natongstatus_time' => time()]);
            $pro_natong = Project::where('id', $pid)->update(['natongstatus_time' => time()]);

            //写一个递归找出 通过项目表的yid字段追溯它关联的以前的id


            $file_res = Storage::put('test.txt', $res);
            $natong_jilu = new NatongRecord();
            $natong_jilu->pid = $pid;
            $natong_jilu->natong_status = $is_incor;
            $natong_jilu->uid = $uid;
            $natong_jilu->edit_time = time();

            $bool = $natong_jilu->save();
        }
        if ($is_incor == 2) {
            $res = Natong::where('pid', '=', $pid)->update(['is_incor' => $is_incor, 'natong_number' => $natong, 'natongstatus_time' => time()]);
            $pro_natong = Project::where('id', $pid)->update(['natongstatus_time' => time()]);

            $this->update_pro($pid, $natong, $uid);

            $natong_jilu = new NatongRecord();
            $natong_jilu->pid = $pid;
            $natong_jilu->natong_status = $is_incor;
            $natong_jilu->natong_number = $natong;
            $natong_jilu->uid = $uid;
            $natong_jilu->edit_time = time();
            $bool = $natong_jilu->save();

        }
        if ($is_incor == 12) {
            $res = Natong::where('pid', '=', $pid)->update(['is_incor' => $is_incor, 'natong_reason' => $natong, 'natongstatus_time' => time()]);
            $pro_natong = Project::where('id', $pid)->update(['natongstatus_time' => time()]);

            $natong_jilu = new NatongRecord();
            $natong_jilu->pid = $pid;
            $natong_jilu->natong_status = $is_incor;
            $natong_jilu->natong_reason = $natong;
            $natong_jilu->uid = $uid;
            $natong_jilu->edit_time = time();
            $bool = $natong_jilu->save();

        }

        if ($bool) {
            return $this->success($bool);
        } else {
            return $this->failed('操作失败');
        }

    }

    //纳统成功后通过pid循环更新追溯到跨年项目更改状态
    public function update_pro($pid, $natong, $uid)
    {

        $natong_jilu = new NatongRecord();
        //查找到项目
        $pro_id = Project::where('id', $pid)->select('id','yid')->first()->toArray();

        if ($pro_id['yid'] && $pro_id['id']) {
            //然后更改项目主表的 natongstatus_time 字段
            $pro_natong = Project::where('id', $pro_id['yid'])->update(['natongstatus_time' => time()]);
            $res = Natong::where('pid', '=', $pro_id['yid'])->update(['is_incor' => 2, 'natong_number' => $natong, 'natongstatus_time' => time()]);

            $natong_jilu->pid = $pro_id['yid'];
            $natong_jilu->natong_status = 2;
            $natong_jilu->natong_number = $natong;
            $natong_jilu->uid = $uid;
            $natong_jilu->edit_time = time();
            $bool = $natong_jilu->save();

            $this->update_pro($pro_id['yid'], $natong, $uid);
        }

    }

    public function getParam(Request $request)
    {

        $res = $request->all();
        $datas = json_decode($res['data'], true);

        $count = $res['count'] ?? '10';
        $now_year = date("Y");
        $year = $res['year'] ?? $now_year;
        $is_incor = $res['is_incor'] ?? '';


        return [$datas, $count, $year, $is_incor];
    }

    public function exportGetParam(Request $request)
    {
        $res = $request->all();
        $datas = json_decode($res['data'], true);

        $count = $res['count'] ?? '10';
        $now_year = date("Y");
        $year = $res['year'] ?? $now_year;
        $is_incor = $res['is_incor'] ?? '';
        $pro_status = $res['pro_status'] ?? '';
        $units = $res['units'] ?? '';
        $type = $res['type'] ?? '';

        return [
            $datas,
            $count,
            $year,
            'is_incor' => $is_incor,
            'pro_status' => $pro_status,
            'units' => $units,
            'type' => $type
        ];
    }

    /**
     * 这是公共的纳统列表请求的方法
     * @param $user_units  用户单位id
     * @param $is_incor   纳统列表当前的纳统状态
     * @param NatongRequest $request
     * @return mixed
     */
    public function common_natong($user_units, $is_incor, $count, $year, NatongRequest $request)
    {
//        dd([$user_units,$is_incor,$count,$year]);
//        $Natong = Natong::with('project:id,type,units_id,pname,pro_status')
//            ->whereIn('is_incor', $is_incor)
//            ->select('id', 'pid', 'is_incor', 'natong_reason', 'natong_number', 'natong_number2', 'natong_month')
//            ->filter($request->all())
//            ->whereHas('project', function ($q) use ($request, $user_units) {
//                $q->where('status_flow', 2)
//                    ->when($user_units == 23, function ($q) {
//                        return $q->whereNotIn('units_id', $this->xianshiqu_units_id);
//                    }, function ($q) use ($user_units) {
//                        return $q->where('units_id', $user_units);
//                    });
//            })
//            ->with('NatongRecord')
//            ->paginate(20);

        $Natong = new NatongCollection(Project::with(['natong' => function ($q) use ($is_incor, $request) {
            $q->select('pid', 'is_incor', 'natongtime', 'natong_reason', 'natong_number', 'natong_number2', 'natong_number3', 'natong_number4', 'natong_number5', 'natong_number6');
        }, 'natong_record:pid,id,uid,edit_time,natong_status'])
            ->select('id', 'type as projectType', 'units_id as createProjecUnit', 'pname as projectName', 'pro_status as projectStatus', 'progress as projectProgress')
            ->whereHas('natong', function ($e) use ($is_incor, $request) {
                $e->whereIn('is_incor', $is_incor)
                    ->select('id', 'is_incor')
                    ->filter($request->all());
            })
            ->where('status_flow', 2)
            ->where('year', $year)
            ->when($user_units == 23, function ($q) {
                return $q->whereNotIn('units_id', $this->xianshiqu_units_id);
            }, function ($q) use ($user_units) {
                return $q->where('units_id', $user_units);
            })
            ->orderBy('natongstatus_time', 'desc')
            ->paginate($count));

        return $this->success($Natong);
    }

    public function export_common_natong($user_units, $is_incor, $count, $year, NatongRequest $request)
    {
        $pro_res = Project::with(['natong' => function ($q) use ($is_incor) {
            $q->select('pid', 'is_incor', 'natong_number', 'natong_number2', 'natong_number3', 'natong_number4', 'natong_number5', 'natong_number6');
        }])
            ->select('id', 'units_id', 'pname', 'type', 'fen_uid', 'year', 'amount', 'amount_now', 'is_new', 'pro_type', 'pro_status', 'progress', 'm_score')
            ->where('status_flow', 2)
            ->where('year', $year)
            ->whereHas('natong', function ($e) use ($is_incor, $request) {
                $e->filter($request->all())
                    ->whereIn('is_incor', $is_incor)
                    ->select('id', 'is_incor');
            })
            ->when($user_units == 23, function ($q) {
                return $q->whereNotIn('units_id', $this->xianshiqu_units_id);
            }, function ($q) use ($user_units) {
                return $q->where('units_id', $user_units);
            })
            ->get()->toArray();

        $get_is_new = array(0 => '新建', 1 => '续建');

        if (is_array($pro_res)) {
            foreach ($pro_res as $k => $v) {
                $pro_res_plan = ProjectPlanCustom::where('pid', $v['id'])->where('m_value', '主体工程')
                    ->where('p_month', '!=', '')->select('id', 'p_month')->get()->toArray();

                if (!empty($pro_res_plan)) {
                    $foo = explode(',', $pro_res_plan[0]['p_month']);
                    $month_id = $foo[0];
                    $pro_proress = Progress::where('custom_id', $pro_res_plan[0]['id'])->value('p_status');
                    $pro_res[$k]['month_id'] = $month_id;
                    $pro_res[$k]['p_status'] = $pro_proress;
                } else {
                    $pro_res[$k]['month_id'] = '';
                    $pro_res[$k]['p_status'] = '';
                }
                $pro_res[$k]['units_id'] = getUnits($v['units_id']);
                $pro_res[$k]['type'] = get_pro_kind($v['type']);
                $pro_res[$k]['fen_uid'] = \App\Models\User::find($v['fen_uid'])->username ?? '';
                $pro_res[$k]['is_new'] = $get_is_new[$v['is_new']];
                $pro_res[$k]['pro_type'] = get_pro_type($v['pro_type']);
                $pro_res[$k]['pro_status'] = get_pro_status($v['pro_status']);
                $pro_res[$k]['progress'] = $v['progress'] . '%';
                $pro_res[$k]['natong']['is_incor'] = getNatongStatus($v['natong']['is_incor']);
                $p_status = $v['p_status'] ?? "0";
                $pro_res[$k]['p_status'] = get_plan_status($p_status);
            }
        }

        return $pro_res;
    }

    /**
     * @param NatongRequest $request
     * @param Natong $Natong
     * @return mixed  系统判定的项目列表
     */
    public function systemDecision(NatongRequest $request)
    {

        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $res = $this->getParam($request);
        $user_units = $res['0']['units_id'] ?? '0';

        $user = Auth::guard('api')->user()->role()->get()->toArray();

        foreach ($user as $k => $v) {
            $role_arr[$k] = $v['pivot']['role_id'];
        }
        $user_role = max($role_arr);

        if ($user_role == 25) {
            //首先获取用户单位，如果用来区分市统计局和县市区统计局

            $count = $res['1'] ?? '10';
            $year = $res['2'];
            //如果是23那就是市统计局
            $is_incor = $res['is_incor'] ?? ['9', '10', '11'];

            $res = $this->common_natong($user_units, $is_incor, $count, $year, $request);
            return $res;

        } else {
            //1、纳统条件如下    2、没有单位判断查出所有的
            $res = $this->getParam($request);
            $is_incor = $res[0]['is_incor'] ?? ['9', '10', '11'];
            $count = $res['1'] ?? '10';
            $year = $res['2'];

            $Natong = new NatongCollection(Project::with(['natong' => function ($q) use ($is_incor, $count) {
                $q->select('pid', 'is_incor', 'natongtime', 'natong_reason', 'natong_number', 'natong_number2', 'natong_number3', 'natong_number4', 'natong_number5', 'natong_number6');

            }])
                ->select('id', 'type as projectType', 'units_id as createProjecUnit', 'pname as projectName', 'pro_status as projectStatus', 'progress as projectProgress')
                ->where('status_flow', 2)
                ->where('year', $year)
                ->whereHas('natong', function ($e) use ($is_incor, $request) {
                    $e->whereIn('is_incor', $is_incor)
                        ->select('id', 'is_incor')
                        ->filter($request->all());
                })
                ->when($user_units == 23, function ($q) {
                    return $q->whereNotIn('units_id', $this->xianshiqu_units_id);
                }, function ($q) use ($user_units) {
                    return $q->where('units_id', $user_units);
                })
                ->orderBy('natongstatus_time', 'desc')
                ->paginate($count));

            return $this->success($Natong);
        }

    }

    //五化办系统判定项目
//    public function wuhuabansystem(NatongRequest $request)
//    {
//        //1、纳统条件如下    2、没有单位判断查出所有的
//        $res = $this->getParam($request);
//        $is_incor = $res[0]['is_incor'] ?? ['9', '10', '11'];
//        $count = $res['1'] ?? '10';
//        $year = $res['2'];
//
//        $Natong = new NatongCollection(Project::with(['natong' => function ($q) use ($is_incor, $count) {
//            $q->select('pid', 'is_incor', 'natongtime');
//        }])
//            ->select('id', 'type as projectType', 'units_id as createProjecUnit', 'pname as projectName', 'pro_status as projectStatus', 'progress as projectProgress')
//            ->where('status_flow', 2)
//            ->where('year', $year)
//            ->whereHas('natong', function ($e) use ($is_incor,$request) {
//                $e->whereIn('is_incor', $is_incor)
//                    ->select('id', 'is_incor')
//                    ->filter($request->all());
//            })
//            ->paginate($count));
//
//        return $this->success($Natong);
//    }

    /**
     * 推送到县市区列表
     * 1、units_id 69, 70, 71, 72, 73, 74, 75
     */
    public function xianshiqu(NatongRequest $request)
    {
//        //项目类型
//        $type = $request->get('type', ['1', '2', '3', '4']);
//
//        $is_incor = $request->get('is_incor', ['9', '10', '11']);
//
//        $pro_status = $request->get('pro_status', 0);
//
//        $pname = $request->get('pname');
//
//        //项目所属地区
//        $units_id = $request->get('units_id', ['69', '70', '71', '72', '73', '74', '75']);
//
//        $data = Natong::with('project:id,type,units_id,pname')
//
//            ->whereIn('is_incor', $is_incor)
//            ->whereHas('project', function ($q) use ($type, $pro_status, $pname, $units_id) {
//                $q->whereHas('wfrun', function ($e) {
//                    $e->where('status', 1);
//                })
//                    ->whereIn('type', $type)
//                    ->WhereIn('units_id', $units_id)
//                    ->where('pro_status', $pro_status)
//                    ->where(function ($query) use ($pname) {
//                        $query->where('pname', 'like', '%' . $pname . '%')->orwhere('id', 'like', '%' . $pname . '%');
//                    });
//            })
//            ->with('NatongRecord')
//            ->paginate();
//
//        return $this->success($data);
        $res = $this->getParam($request);
        $count = $res['1'] ?? '10';
        $is_incor = $res['is_incor'] ?? ['9', '10', '11'];
        $year = $res['2'];

        $Natong = new NatongCollection(Project::with(['natong' => function ($q) use ($is_incor, $count) {
            $q->select('pid', 'is_incor', 'natongtime', 'natong_reason', 'natong_number', 'natong_number2', 'natong_number3', 'natong_number4', 'natong_number5', 'natong_number6');
        }, 'natong_record:pid,id,uid,edit_time,natong_status'])
            ->select('id', 'type as projectType', 'units_id as createProjecUnit', 'pname as projectName', 'pro_status as projectStatus', 'progress as projectProgress')
            //只查询县市区的数据
            ->whereIn('units_id', $this->xianshiqu_units_id)
            ->where('status_flow', 2)
            ->where('year', $year)
            ->whereHas('natong', function ($e) use ($is_incor, $request) {
                $e->whereIn('is_incor', $is_incor)
                    ->select('id', 'is_incor')
                    ->filter($request->all());
            })
            ->orderBy('natongstatus_time', 'desc')
            ->paginate($count));

        return $this->success($Natong);
    }


    //未纳统项目
    Public function weinatong(NatongRequest $request)
    {
        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $res = $this->getParam($request);

        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $user_units = $res['0']['units_id'] ?? '0';
        $count = $res['1'] ?? '10';
        $now_year = date("Y");
        $year = $res['year'] ?? $now_year;

        //如果是23那就是市统计局
        $is_incor = $res['is_incor'] ?? ['13', '14'];

        $res = $this->common_natong($user_units, $is_incor, $count, $year, $request);

        return $res;

    }

    //应该未统项目
    Public function yingtong(NatongRequest $request)
    {
        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $res = $this->getParam($request);

        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $user_units = $res['0']['units_id'] ?? '0';
        $count = $res['1'] ?? '10';
        $now_year = date("Y");
        $year = $res['year'] ?? $now_year;

        //如果是23那就是市统计局
        $is_incor = $res['is_incor'] ?? ['1', '3', '4', '5', '6', '7', '8'];

        $res = $this->common_natong($user_units, $is_incor, $count, $year, $request);

        return $res;

    }

    //已纳统项目
    Public function yinatong(NatongRequest $request)
    {

        $res = $this->getParam($request);

        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $user_units = $res['0']['units_id'] ?? '0';
        $count = $res['1'] ?? '10';
        $now_year = date("Y");
        $year = $res['year'] ?? $now_year;

        $is_incor = $res['is_incor'] ?? [2];

        $res = $this->common_natong($user_units, $is_incor, $count, $year, $request);
        return $res;

    }

    //已撤销项目
    Public function chexiao(NatongRequest $request)
    {
        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $res = $this->getParam($request);
        $user_units = $res['0']['units_id'] ?? '0';

        $count = $res['1'] ?? '10';
        $now_year = date("Y");
        $year = $res['year'] ?? $now_year;

        $user = Auth::guard('api')->user()->role()->get()->toArray();
        foreach ($user as $k => $v) {
            $role_arr[$k] = $v['pivot']['role_id'];
        }
        $user_role = max($role_arr);

        if ($user_role == 25) {
            //1、纳统条件如下    2、没有单位判断查出所有的
            $res = $this->getParam($request);
            $is_incor = $res[0]['is_incor'] ?? ['12'];
            $count = $res['1'] ?? '10';
            $year = $res['2'];

            $data = new NatongCollection(Project::with(['natong' => function ($q) use ($is_incor, $count) {
                $q->select('pid', 'is_incor', 'natongtime', 'natong_reason', 'natong_number', 'natong_number2', 'natong_number3', 'natong_number4', 'natong_number5', 'natong_number6');
            }, 'natong:pid,natong_reason,natong_number,natong_number2,natong_number3'])
                ->select('id', 'type as projectType', 'units_id as createProjecUnit', 'pname as projectName', 'pro_status as projectStatus', 'progress as projectProgress')
                ->where('status_flow', 2)
                ->where('year', $year)
                ->whereHas('natong', function ($e) use ($is_incor, $request) {
                    $e->whereIn('is_incor', $is_incor)
                        ->select('id', 'is_incor')
                        ->filter($request->all());
                })
                ->orderBy('natongstatus_time', 'desc')
                ->paginate($count));


        } else {
            //撤销的纳统状态
            $is_incor = $request->get('is_incor', ['12']);

            $data = $this->common_natong($user_units, $is_incor, $count, $year, $request);
        }

        return $data;
    }

    //五化办系统判定项目
//    public function wuhuabanchexiao(NatongRequest $request)
//    {
//        //1、纳统条件如下    2、没有单位判断查出所有的
//        $res = $this->getParam($request);
//        $is_incor = $res[0]['is_incor'] ?? ['12'];
//        $count = $res['1'] ?? '10';
//        $year = $res['2'];
//
//        $Natong = new NatongCollection(Project::with(['natong' => function ($q) use ($is_incor, $count) {
//            $q->select('pid', 'is_incor', 'natongtime');
//        }])
//            ->select('id', 'type as projectType', 'units_id as createProjecUnit', 'pname as projectName', 'pro_status as projectStatus', 'progress as projectProgress')
//            ->where('status_flow', 2)
//            ->where('year', $year)
//            ->whereHas('natong', function ($e) use ($is_incor, $request) {
//                $e->whereIn('is_incor', $is_incor)
//                    ->select('id', 'is_incor')
//                    ->filter($request->all());
//            })
//            ->paginate($count));
//
//        return $this->success($Natong);
//    }

    //被提醒项目
    Public function receive(NatongRequest $request)
    {
        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $res = $this->getParam($request);
        $count = $res['1'] ?? '10';
        $year = $res['2'];

        $is_incor = $res['is_incor'] ?? ['1', '2', '3', '4', '5', '6', '7', '8', '13', '14', '12'];
        $user = Auth::guard('api')->user()->toArray();

        if ($user['id']) {
            $user_units = $user['id'];
        } else {
            return $this->failed('没有用户信息');
        }

        $Natong = new NatongCollection(Project::with(['natong' => function ($q) use ($is_incor, $count) {
            $q->select('pid', 'is_incor', 'natongtime', 'natong_reason', 'natong_number', 'natong_number2', 'natong_number3', 'natong_number4', 'natong_number5', 'natong_number6');
        }, 'natong_record:pid,id,uid,edit_time,natong_status'])
            ->select('id', 'type as projectType', 'units_id as createProjecUnit', 'pname as projectName', 'pro_status as projectStatus', 'progress as projectProgress')
            ->where('status_flow', 2)
            ->where('year', $year)
            ->whereHas('natong', function ($e) use ($is_incor, $request) {
                $e->whereIn('is_incor', $is_incor)
                    ->select('id', 'is_incor')
                    ->filter($request->all());
            })
            ->where('units_id', $user_units)
            ->orderBy('natongstatus_time', 'desc')
            ->paginate($count));

        return $this->success($Natong);
    }

    //全市纳统项目
    public function quanshi(NatongRequest $request)
    {

        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $res = $this->getParam($request);

        $count = $res['1'] ?? '10';
        $year = $res['2'];

        //获取到的纳统状态
        $is_incor = $res[3][0] ?? ['1', '2', '3', '4', '5', '6', '7', '8', '13', '14'];
        //判断传递过来的 纳统查询条件   未纳统   应统未统    已纳统
        if ($is_incor == 1) {
            $is_incor = [1, 3, 4, 5, 6, 7, 8];
        }
        if ($is_incor == 2) {
            $is_incor = [2];
        }
        if ($is_incor == 3) {
            $is_incor = [13, 14];
        }

        $user = Auth::guard('api')->user()->toArray();
        if ($user['id']) {
            $user_units = $user['id'];
        } else {
            return $this->failed('没有用户信息');
        }

        $Natong = new NatongCollection(Project::with(['natong' => function ($q) use ($is_incor, $count) {
            $q->select('pid', 'is_incor', 'natongtime', 'natong_reason', 'natong_number', 'natong_number2', 'natong_number3', 'natong_number4', 'natong_number5', 'natong_number6');
        }, 'natong_record:pid,id,uid,edit_time,natong_status'])
            ->select('id', 'type as projectType', 'units_id as createProjecUnit', 'pname as projectName', 'pro_status as projectStatus', 'progress as projectProgress')
            ->where('status_flow', 2)
            ->where('year', $year)
            ->whereHas('natong', function ($e) use ($is_incor, $request) {
                $e->filter($request->all())
                    ->whereIn('is_incor', $is_incor)
                    ->select('id', 'is_incor');
            })
            ->orderBy('natongstatus_time', 'desc')
            ->paginate($count));

        return $this->success($Natong);
    }

    //全市纳统类表打出
    public function exportQuanshi(NatongRequest $request)
    {
        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $res = $request->all();
        if ($res['data']) {
            $datas = json_decode($res['data'], true);
        }

        $units_id = $datas['units_id'] ?? '';

        $year = $res['year'] ?? '';

        //获取到的纳统状态
        $is_incor = $res['is_incor'] ?? ['1', '2', '3', '4', '5', '6', '7', '8', '13', '14'];

        //判断传递过来的 纳统查询条件   未纳统   应统未统    已纳统
        if ($is_incor == 1) {
            $is_incor = [1, 3, 4, 5, 6, 7, 8];
        }
        if ($is_incor == 2) {
            $is_incor = [2];
        }
        if ($is_incor == 3) {
            $is_incor = [13, 14];
        }

        $user = Auth::guard('api')->user()->toArray();
        if ($user['id']) {
            $user_units = $user['id'];
        } else {
            return $this->failed('没有用户信息');
        }

        //入库后的项目状态
        $pro_status = $res['pro_status'] ?? '';
        $pro_status = $pro_status ? array($pro_status) : [0, 1, 2, 3, 4];

        //获取到项目类型
        if (isset($res['type'])) {
            $type = [$res['type']];
        } else {
            $type = [1, 2, 3, 4];
        }

        //获取到项目所属地址
        $units = $res['units'] ?? '';
        if ($units == 2) {
            $units = [69];
        } else if ($units == 3) {
            $units = [70];
        } else if ($units == 4) {
            $units = [71];
        } else if ($units == 5) {
            $units = [75];
        } else if ($units == 6) {
            $units = [72];
        } else if ($units == 7) {
            $units = [73];
        } else if ($units == 8) {
            $units = [74];
        }

        //项目名称或者id
        $pname = $res['pname'] ?? '';
        return $this->common_daochu($year, $units_id, $pro_status, $type, $is_incor, $units, $pname);

//        $pro_res = Project::with(['natong' => function ($q) use ($is_incor, $count) {
//            $q->select('pid', 'is_incor', 'natong_number', 'natong_number2', 'natong_number3', 'natong_number4', 'natong_number5', 'natong_number6');
//        }])
//            ->select('id', 'units_id', 'pname', 'type', 'fen_uid', 'year', 'amount', 'amount_now', 'is_new', 'pro_type', 'pro_status', 'progress', 'm_score')
//            ->where('status_flow', 2)
//            ->where('year', $year)
//            ->whereIn('type', $type)
//            ->whereIn('pro_status', $pro_status)
//            ->whereHas('natong', function ($e) use ($is_incor, $request) {
//                $e->whereIn('is_incor', $is_incor)
//                    ->select('id', 'is_incor');
//            })
//            ->where(function ($query) use ($pname) {
//                $query->where('pname', 'like', '%' . $pname . '%')
//                    ->orwhere('id', 'like', '%' . $pname . '%');
//            })
//            ->when($units == '', function ($q) use ($units) {
//            }, function ($q) use ($units) {
//                return $q->where('units_id', $units);
//            })
//            ->get()
//            ->toArray();
//
//        $get_is_new = array(0 => '新建', 1 => '续建');
//
//        foreach ($pro_res as $k => $v) {
//            $pro_res_plan = ProjectPlanCustom::where('pid', $v['id'])->where('m_value', '主体工程')
//                ->where('p_month', '!=', '')->select('id', 'p_month')->get()->toArray();
//
//            if (!empty($pro_res_plan)) {
//                $foo = explode(',', $pro_res_plan[0]['p_month']);
//                $month_id = $foo[0];
//                $pro_proress = Progress::where('custom_id', $pro_res_plan[0]['id'])->value('p_status');
//                $pro_res[$k]['month_id'] = $month_id;
//                $pro_res[$k]['p_status'] = $pro_proress;
//            } else {
//                $pro_res[$k]['month_id'] = '';
//                $pro_res[$k]['p_status'] = '';
//            }
//
//            $pro_res[$k]['units_id'] = getUnits($v['units_id']);
//            $pro_res[$k]['type'] = get_pro_kind($v['type']);
//            $pro_res[$k]['fen_uid'] = \App\Models\User::find($v['fen_uid'])->username ?? '';
//            $pro_res[$k]['is_new'] = $get_is_new[$v['is_new']];
//            $pro_res[$k]['pro_type'] = get_pro_type($v['pro_type']);
//            $pro_res[$k]['pro_status'] = get_pro_status($v['pro_status']);
//            $pro_res[$k]['progress'] = $v['progress'] . '%';
//            $pro_res[$k]['natong']['is_incor'] = getNatongStatus($v['natong']['is_incor']);
//            $p_status = $v['p_status'] ?? "0";
//            $pro_res[$k]['p_status'] = get_plan_status($p_status);
//        }
//
//        try {
//            $LoginLogs = $pro_res;
//
//            $rows = [
//                ['ID', '立项单位', '项目名称', '项目分类', '分管市领导', '年份', '总投资金额(万元)',
//                    '当年投资金额(万元)', '新建/续建', '重点级别', '项目状态', '项目进度', '当月评分',
//                    '主体工程', '填报进度', '纳统状态', '纳统代码1', '纳统代码2', '纳统代码3', '纳统代码4',
//                    '纳统代码5', '纳统代码6'],
//            ];
//
//            foreach ($LoginLogs as $item) {
//                $rows[] = [
//                    $item['id'],
//                    $item['units_id'],
//                    $item['pname'],
//                    $item['type'],
//                    $item['fen_uid'],
//                    $item['year'],
//                    $item['amount'],
//                    $item['amount_now'],
//                    $item['is_new'],
//                    $item['pro_type'],
//                    $item['pro_status'],
//                    $item['progress'],
//                    $item['m_score'],
//                    $item['month_id'],
//                    $item['p_status'],
//                    $item['natong']['is_incor'],
//                    $item['natong']['natong_number'],
//                    $item['natong']['natong_number2'],
//                    $item['natong']['natong_number3'],
//                    $item['natong']['natong_number4'],
//                    $item['natong']['natong_number5'],
//                    $item['natong']['natong_number6'],
//                ];
//            }
//            $excelFileName = 'NatongQuanshi' . date('YmdHis');
//
//            $filePath = public_path('office/natong');
//            Excel::create($excelFileName, function (laravelExcelWriter $excel) use ($excelFileName, $rows) {
//                $excel->setTitle($excelFileName);
//                $excel->sheet('全市纳统项目', function (\PHPExcel_Worksheet $sheet) use ($rows) {
//                    $sheet->fromArray($rows);
//                });
//            })->store('xls', $filePath);
//
//            $fileUrl = URL::to('/') . '/office/natong/' . $excelFileName . '.xls';
//
//            return $fileUrl;
//
//        } catch (\Exception $e) {
//
//            return $this->failed($e->getMessage(), 500);
//
//        }
    }

    //系统判定项目导出excel
    public function exportDecision(NatongRequest $request)
    {
        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $user = Auth::guard('api')->user()->role()->get()->toArray();
        foreach ($user as $k => $v) {
            $role_arr[$k] = $v['pivot']['role_id'];
        }
        $user_role = max($role_arr);

        $res = $request->all();
        if ($res['data']) {
            $datas = json_decode($res['data'], true);
        }

        $units_id = $datas['units_id'] ?? '';

        $year = $res['year'] ?? '';

        //获取到的纳统状态
        $is_incor = $res['is_incor'] ?? ['9', '10', '11'];

        $user = Auth::guard('api')->user()->toArray();
        if ($user['id']) {
            $user_units = $user['id'];
        } else {
            return $this->failed('没有用户信息');
        }

        //入库后的项目状态
        $pro_status = $res['pro_status'] ?? '';
        $pro_status = $pro_status ? array($pro_status) : [0, 1, 2, 3, 4];

        //获取到项目类型
        if (isset($res['type'])) {
            $type = [$res['type']];
        } else {
            $type = [1, 2, 3, 4];
        }

        //获取到项目所属地址
        $units = $res['units'] ?? '';

        //项目名称或者id
        $pname = $res['pname'] ?? '';

        //roleid=25就是五化办角色，可以看到所有市级和县市区平台项目
        if ($user_role == 25) {
            //五化办可以看所有的
            return $this->common_daochu($year, $units_id, $pro_status, $type, $is_incor, $units, $pname);

        } else {
            //  县市区还是看各自县市区的项目
            return $this->decision_daochu($year, $units_id, $pro_status, $type, $is_incor, $units, $pname);
        }
    }

    //是统计局看县市区项目列表导出
    public function exportXianshiqu(NatongRequest $request)
    {
        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $user = Auth::guard('api')->user()->role()->get()->toArray();
        foreach ($user as $k => $v) {
            $role_arr[$k] = $v['pivot']['role_id'];
        }
        $user_role = max($role_arr);

        $res = $request->all();
        if ($res['data']) {
            $datas = json_decode($res['data'], true);
        }

        $units_id = $datas['units_id'] ?? '';

        $year = $res['year'] ?? '';

        //获取到的纳统状态
        $is_incor = $res['is_incor'] ?? ['9', '10', '11'];

        $user = Auth::guard('api')->user()->toArray();
        if ($user['id']) {
            $user_units = $user['id'];
        } else {
            return $this->failed('没有用户信息');
        }

        //入库后的项目状态
        $pro_status = $res['pro_status'] ?? '';
        $pro_status = $pro_status ? array($pro_status) : [0, 1, 2, 3, 4];

        //获取到项目类型
        if (isset($res['type'])) {
            $type = [$res['type']];
        } else {
            $type = [1, 2, 3, 4];
        }

        //获取到项目所属地址
        $units = $res['units'] ?? '';

        //项目名称或者id
        $pname = $res['pname'] ?? '';

        //roleid=25就是五化办角色，可以看到所有市级和县市区平台项目

        //  市统计局看县市区的项目
        return $this->xianshiqu_daochu($year, $units_id, $pro_status, $type, $is_incor, $units, $pname);

    }

    //未纳统导出
    public function exportWeinatong(NatongRequest $request)
    {
        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $res = $request->all();
        if ($res['data']) {
            $datas = json_decode($res['data'], true);
        }

        $units_id = $datas['units_id'] ?? '';

        $year = $res['year'] ?? '';

        //获取到的纳统状态
        $is_incor = $res['is_incor'] ?? ['13', '14'];

        $user = Auth::guard('api')->user()->toArray();
        if ($user['id']) {
            $user_units = $user['id'];
        } else {
            return $this->failed('没有用户信息');
        }

        //入库后的项目状态
        $pro_status = $res['pro_status'] ?? '';
        $pro_status = $pro_status ? array($pro_status) : [0, 1, 2, 3, 4];

        //获取到项目类型
        if (isset($res['type'])) {
            $type = [$res['type']];
        } else {
            $type = [1, 2, 3, 4];
        }

        //获取到项目所属地址
        $units = $res['units'] ?? '';

        //项目名称或者id
        $pname = $res['pname'] ?? '';
        return $this->decision_daochu($year, $units_id, $pro_status, $type, $is_incor, $units, $pname);
    }

    //应统未统列表导出
    public function exportYingtong(NatongRequest $request)
    {
        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $res = $request->all();
        if ($res['data']) {
            $datas = json_decode($res['data'], true);
        }

        $units_id = $datas['units_id'] ?? '';

        $year = $res['year'] ?? '';

        //获取到的纳统状态
        $is_incor = $res['is_incor'] ?? ['1', '3', '4', '5', '6', '7', '8'];

        $user = Auth::guard('api')->user()->toArray();
        if ($user['id']) {
            $user_units = $user['id'];
        } else {
            return $this->failed('没有用户信息');
        }

        //入库后的项目状态
        $pro_status = $res['pro_status'] ?? '';
        $pro_status = $pro_status ? array($pro_status) : [0, 1, 2, 3, 4];

        //获取到项目类型
        if (isset($res['type'])) {
            $type = [$res['type']];
        } else {
            $type = [1, 2, 3, 4];
        }

        //获取到项目所属地址
        $units = $res['units'] ?? '';

        //项目名称或者id
        $pname = $res['pname'] ?? '';
        return $this->decision_daochu($year, $units_id, $pro_status, $type, $is_incor, $units, $pname);
    }

    //已纳统列表导出
    public function exportYinatong(NatongRequest $request)
    {
        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $res = $request->all();
        if ($res['data']) {
            $datas = json_decode($res['data'], true);
        }

        $units_id = $datas['units_id'] ?? '';

        $year = $res['year'] ?? '';

        //获取到的纳统状态
        $is_incor = $res['is_incor'] ?? ['2'];

        $user = Auth::guard('api')->user()->toArray();
        if ($user['id']) {
            $user_units = $user['id'];
        } else {
            return $this->failed('没有用户信息');
        }

        //入库后的项目状态
        $pro_status = $res['pro_status'] ?? '';
        $pro_status = $pro_status ? array($pro_status) : [0, 1, 2, 3, 4];

        //获取到项目类型
        if (isset($res['type'])) {
            $type = [$res['type']];
        } else {
            $type = [1, 2, 3, 4];
        }

        //获取到项目所属地址
        $units = $res['units'] ?? '';

        //项目名称或者id
        $pname = $res['pname'] ?? '';
        return $this->decision_daochu($year, $units_id, $pro_status, $type, $is_incor, $units, $pname);
    }

    //已纳统列表导出
    public function exportChexiao(NatongRequest $request)
    {
        //首先获取用户单位，如果用来区分市统计局和县市区统计局
        $res = $request->all();
        if ($res['data']) {
            $datas = json_decode($res['data'], true);
        }

        $units_id = $datas['units_id'] ?? '';

        $year = $res['year'] ?? '';

        //获取到的纳统状态
        $is_incor = $res['is_incor'] ?? ['12'];

        $user = Auth::guard('api')->user()->role()->get()->toArray();
        foreach ($user as $k => $v) {
            $role_arr[$k] = $v['pivot']['role_id'];
        }
        $user_role = max($role_arr);

        //入库后的项目状态
        $pro_status = $res['pro_status'] ?? '';
        $pro_status = $pro_status ? array($pro_status) : [0, 1, 2, 3, 4];

        //获取到项目类型
        if (isset($res['type'])) {
            $type = [$res['type']];
        } else {
            $type = [1, 2, 3, 4];
        }

        //获取到项目所属地址
        $units = $res['units'] ?? '';

        //项目名称或者id
        $pname = $res['pname'] ?? '';

        if ($user_role == 25) {
            //五化办可以看所有的
            return $this->common_daochu($year, $units_id, $pro_status, $type, $is_incor, $units, $pname);

        } else {
            //  县市区还是看各自县市区的项目
            return $this->decision_daochu($year, $units_id, $pro_status, $type, $is_incor, $units, $pname);
        }
    }

    //全市纳统项目
    public function common_daochu($year, $units_id, $pro_status, $type, $is_incor, $units, $pname)
    {

        $pro_res = Project::with(['natong' => function ($q) use ($is_incor) {
            $q->select('pid', 'is_incor', 'natong_number', 'natong_number2', 'natong_number3', 'natong_number4', 'natong_number5', 'natong_number6');
        }])
            ->select('id', 'units_id', 'pname', 'type', 'fen_uid', 'year', 'amount', 'amount_now', 'is_new', 'pro_type', 'pro_status', 'progress', 'm_score')
            ->where('status_flow', 2)
            ->where('year', $year)
            ->whereIn('type', $type)
            ->whereIn('pro_status', $pro_status)
            ->whereHas('natong', function ($e) use ($is_incor) {
                $e->whereIn('is_incor', $is_incor)
                    ->select('id', 'is_incor');
            })
            ->where(function ($query) use ($pname) {
                $query->where('pname', 'like', '%' . $pname . '%')
                    ->orwhere('id', 'like', '%' . $pname . '%');
            })
            //要判断提交的单位id和用户的单位id，如果有单位id就要查询对应的单位，
            ->when($units == '', function ($q) use ($units) {

            }, function ($q) use ($units) {
                return $q->where('units_id', $units);
            })
            ->get()
            ->toArray();

        $get_is_new = array(0 => '新建', 1 => '续建');

        foreach ($pro_res as $k => $v) {
            $pro_res_plan = ProjectPlanCustom::where('pid', $v['id'])->where('m_value', '主体工程')
                ->where('p_month', '!=', '')->select('id', 'p_month')->get()->toArray();

            if (!empty($pro_res_plan)) {
                $foo = explode(',', $pro_res_plan[0]['p_month']);
                $month_id = $foo[0];
                $pro_proress = Progress::where('custom_id', $pro_res_plan[0]['id'])->value('p_status');
                $pro_res[$k]['month_id'] = $month_id;
                $pro_res[$k]['p_status'] = $pro_proress;
            } else {
                $pro_res[$k]['month_id'] = '';
                $pro_res[$k]['p_status'] = '';
            }

            $pro_res[$k]['units_id'] = getUnits($v['units_id']);
            $pro_res[$k]['type'] = get_pro_kind($v['type']);
            $pro_res[$k]['fen_uid'] = \App\Models\User::find($v['fen_uid'])->username ?? '';
            $pro_res[$k]['is_new'] = $get_is_new[$v['is_new']];
            $pro_res[$k]['pro_type'] = get_pro_type($v['pro_type']);
            $pro_res[$k]['pro_status'] = get_pro_status($v['pro_status']);
            $pro_res[$k]['progress'] = $v['progress'] . '%';
            $pro_res[$k]['natong']['is_incor'] = getNatongStatus($v['natong']['is_incor']);
            $p_status = $v['p_status'] ?? "0";
            $pro_res[$k]['p_status'] = get_plan_status($p_status);
        }

        try {
            $LoginLogs = $pro_res;

            $rows = [
                ['ID', '立项单位', '项目名称', '项目分类', '分管市领导', '年份', '总投资金额(万元)',
                    '当年投资金额(万元)', '新建/续建', '重点级别', '项目状态', '项目进度', '当月评分',
                    '主体工程', '填报进度', '纳统状态', '纳统代码1', '纳统代码2', '纳统代码3', '纳统代码4',
                    '纳统代码5', '纳统代码6'],
            ];

            foreach ($LoginLogs as $item) {
                $rows[] = [
                    $item['id'],
                    $item['units_id'],
                    $item['pname'],
                    $item['type'],
                    $item['fen_uid'],
                    $item['year'],
                    $item['amount'],
                    $item['amount_now'],
                    $item['is_new'],
                    $item['pro_type'],
                    $item['pro_status'],
                    $item['progress'],
                    $item['m_score'],
                    $item['month_id'],
                    $item['p_status'],
                    $item['natong']['is_incor'],
                    $item['natong']['natong_number'],
                    $item['natong']['natong_number2'],
                    $item['natong']['natong_number3'],
                    $item['natong']['natong_number4'],
                    $item['natong']['natong_number5'],
                    $item['natong']['natong_number6'],
                ];
            }
            $excelFileName = 'NatongQuanshi' . date('YmdHis');

            $filePath = public_path('office/natong');
            Excel::create($excelFileName, function (laravelExcelWriter $excel) use ($excelFileName, $rows) {
                $excel->setTitle($excelFileName);
                $excel->sheet('全市纳统项目', function (\PHPExcel_Worksheet $sheet) use ($rows) {
                    $sheet->fromArray($rows);
                });
            })->store('xls', $filePath);

            $fileUrl = URL::to('/') . '/office/natong/' . $excelFileName . '.xls';

            return $fileUrl;

        } catch (\Exception $e) {

            return $this->failed($e->getMessage(), 500);

        }
    }


    //系统判定纳统项目到处  统计局角色专属
    public function decision_daochu($year, $units_id, $pro_status, $type, $is_incor, $units, $pname)
    {

        $pro_res = Project::with(['natong' => function ($q) use ($is_incor) {
            $q->select('pid', 'is_incor', 'natong_number', 'natong_number2', 'natong_number3', 'natong_number4', 'natong_number5', 'natong_number6');
        }])
            ->select('id', 'units_id', 'pname', 'type', 'fen_uid', 'year', 'amount', 'amount_now', 'is_new', 'pro_type', 'pro_status', 'progress', 'm_score')
            ->where('status_flow', 2)
            ->where('year', $year)
            ->whereIn('type', $type)
            ->whereIn('pro_status', $pro_status)
            ->whereHas('natong', function ($e) use ($is_incor) {
                $e->whereIn('is_incor', $is_incor)
                    ->select('id', 'is_incor');
            })
            ->where(function ($query) use ($pname) {
                $query->where('pname', 'like', '%' . $pname . '%')
                    ->orwhere('id', 'like', '%' . $pname . '%');
            })
            //要判断提交的单位id和用户的单位id，如果有单位id就要查询对应的单位，
            ->when($units_id == 23, function ($q) {
                return $q->whereNotIn('units_id', $this->xianshiqu_units_id);
            }, function ($q) use ($units_id) {
                return $q->where('units_id', $units_id);
            })
            ->get()
            ->toArray();

        $get_is_new = array(0 => '新建', 1 => '续建');

        foreach ($pro_res as $k => $v) {
            $pro_res_plan = ProjectPlanCustom::where('pid', $v['id'])->where('m_value', '主体工程')
                ->where('p_month', '!=', '')->select('id', 'p_month')->get()->toArray();

            if (!empty($pro_res_plan)) {
                $foo = explode(',', $pro_res_plan[0]['p_month']);
                $month_id = $foo[0];
                $pro_proress = Progress::where('custom_id', $pro_res_plan[0]['id'])->value('p_status');
                $pro_res[$k]['month_id'] = $month_id;
                $pro_res[$k]['p_status'] = $pro_proress;
            } else {
                $pro_res[$k]['month_id'] = '';
                $pro_res[$k]['p_status'] = '';
            }

            $pro_res[$k]['units_id'] = getUnits($v['units_id']);
            $pro_res[$k]['type'] = get_pro_kind($v['type']);
            $pro_res[$k]['fen_uid'] = \App\Models\User::find($v['fen_uid'])->username ?? '';
            $pro_res[$k]['is_new'] = $get_is_new[$v['is_new']];
            $pro_res[$k]['pro_type'] = get_pro_type($v['pro_type']);
            $pro_res[$k]['pro_status'] = get_pro_status($v['pro_status']);
            $pro_res[$k]['progress'] = $v['progress'] . '%';
            $pro_res[$k]['natong']['is_incor'] = getNatongStatus($v['natong']['is_incor']);
            $p_status = $v['p_status'] ?? "0";
            $pro_res[$k]['p_status'] = get_plan_status($p_status);
        }

        try {
            $LoginLogs = $pro_res;

            $rows = [
                ['ID', '立项单位', '项目名称', '项目分类', '分管市领导', '年份', '总投资金额(万元)',
                    '当年投资金额(万元)', '新建/续建', '重点级别', '项目状态', '项目进度', '当月评分',
                    '主体工程', '填报进度', '纳统状态', '纳统代码1', '纳统代码2', '纳统代码3', '纳统代码4',
                    '纳统代码5', '纳统代码6'],
            ];

            foreach ($LoginLogs as $item) {
                $rows[] = [
                    $item['id'],
                    $item['units_id'],
                    $item['pname'],
                    $item['type'],
                    $item['fen_uid'],
                    $item['year'],
                    $item['amount'],
                    $item['amount_now'],
                    $item['is_new'],
                    $item['pro_type'],
                    $item['pro_status'],
                    $item['progress'],
                    $item['m_score'],
                    $item['month_id'],
                    $item['p_status'],
                    $item['natong']['is_incor'],
                    $item['natong']['natong_number'],
                    $item['natong']['natong_number2'],
                    $item['natong']['natong_number3'],
                    $item['natong']['natong_number4'],
                    $item['natong']['natong_number5'],
                    $item['natong']['natong_number6'],
                ];
            }
            $excelFileName = 'NatongQuanshi' . date('YmdHis');

            $filePath = public_path('office/natong');
            Excel::create($excelFileName, function (laravelExcelWriter $excel) use ($excelFileName, $rows) {
                $excel->setTitle($excelFileName);
                $excel->sheet('全市纳统项目', function (\PHPExcel_Worksheet $sheet) use ($rows) {
                    $sheet->fromArray($rows);
                });
            })->store('xls', $filePath);

            $fileUrl = URL::to('/') . '/office/natong/' . $excelFileName . '.xls';

            return $fileUrl;

        } catch (\Exception $e) {

            return $this->failed($e->getMessage(), 500);

        }
    }

    //市统计局角色查看县市区纳统情况列表
    public function xianshiqu_daochu($year, $units_id, $pro_status, $type, $is_incor, $units, $pname)
    {

        $pro_res = Project::with(['natong' => function ($q) use ($is_incor) {
            $q->select('pid', 'is_incor', 'natong_number', 'natong_number2', 'natong_number3', 'natong_number4', 'natong_number5', 'natong_number6');
        }])
            ->select('id', 'units_id', 'pname', 'type', 'fen_uid', 'year', 'amount', 'amount_now', 'is_new', 'pro_type', 'pro_status', 'progress', 'm_score')
            ->where('status_flow', 2)
            ->where('year', $year)
            ->whereIn('type', $type)
            ->whereIn('pro_status', $pro_status)
            ->whereHas('natong', function ($e) use ($is_incor) {
                $e->whereIn('is_incor', $is_incor)
                    ->select('id', 'is_incor');
            })
            ->where(function ($query) use ($pname) {
                $query->where('pname', 'like', '%' . $pname . '%')
                    ->orwhere('id', 'like', '%' . $pname . '%');
            })
            //要判断提交的单位id和用户的单位id，如果有单位id就要查询对应的单位，
            ->whereIn('units_id', $this->xianshiqu_units_id)
            ->get()
            ->toArray();

        $get_is_new = array(0 => '新建', 1 => '续建');

        foreach ($pro_res as $k => $v) {
            $pro_res_plan = ProjectPlanCustom::where('pid', $v['id'])->where('m_value', '主体工程')
                ->where('p_month', '!=', '')->select('id', 'p_month')->get()->toArray();

            if (!empty($pro_res_plan)) {
                $foo = explode(',', $pro_res_plan[0]['p_month']);
                $month_id = $foo[0];
                $pro_proress = Progress::where('custom_id', $pro_res_plan[0]['id'])->value('p_status');
                $pro_res[$k]['month_id'] = $month_id;
                $pro_res[$k]['p_status'] = $pro_proress;
            } else {
                $pro_res[$k]['month_id'] = '';
                $pro_res[$k]['p_status'] = '';
            }

            $pro_res[$k]['units_id'] = getUnits($v['units_id']);
            $pro_res[$k]['type'] = get_pro_kind($v['type']);
            $pro_res[$k]['fen_uid'] = \App\Models\User::find($v['fen_uid'])->username ?? '';
            $pro_res[$k]['is_new'] = $get_is_new[$v['is_new']];
            $pro_res[$k]['pro_type'] = get_pro_type($v['pro_type']);
            $pro_res[$k]['pro_status'] = get_pro_status($v['pro_status']);
            $pro_res[$k]['progress'] = $v['progress'] . '%';
            $pro_res[$k]['natong']['is_incor'] = getNatongStatus($v['natong']['is_incor']);
            $p_status = $v['p_status'] ?? "0";
            $pro_res[$k]['p_status'] = get_plan_status($p_status);
        }

        try {
            $LoginLogs = $pro_res;

            $rows = [
                ['ID', '立项单位', '项目名称', '项目分类', '分管市领导', '年份', '总投资金额(万元)',
                    '当年投资金额(万元)', '新建/续建', '重点级别', '项目状态', '项目进度', '当月评分',
                    '主体工程', '填报进度', '纳统状态', '纳统代码1', '纳统代码2', '纳统代码3', '纳统代码4',
                    '纳统代码5', '纳统代码6'],
            ];

            foreach ($LoginLogs as $item) {
                $rows[] = [
                    $item['id'],
                    $item['units_id'],
                    $item['pname'],
                    $item['type'],
                    $item['fen_uid'],
                    $item['year'],
                    $item['amount'],
                    $item['amount_now'],
                    $item['is_new'],
                    $item['pro_type'],
                    $item['pro_status'],
                    $item['progress'],
                    $item['m_score'],
                    $item['month_id'],
                    $item['p_status'],
                    $item['natong']['is_incor'],
                    $item['natong']['natong_number'],
                    $item['natong']['natong_number2'],
                    $item['natong']['natong_number3'],
                    $item['natong']['natong_number4'],
                    $item['natong']['natong_number5'],
                    $item['natong']['natong_number6'],
                ];
            }
            $excelFileName = 'NatongQuanshi' . date('YmdHis');

            $filePath = public_path('office/natong');
            Excel::create($excelFileName, function (laravelExcelWriter $excel) use ($excelFileName, $rows) {
                $excel->setTitle($excelFileName);
                $excel->sheet('全市纳统项目', function (\PHPExcel_Worksheet $sheet) use ($rows) {
                    $sheet->fromArray($rows);
                });
            })->store('xls', $filePath);

            $fileUrl = URL::to('/') . '/office/natong/' . $excelFileName . '.xls';

            return $fileUrl;

        } catch (\Exception $e) {

            return $this->failed($e->getMessage(), 500);

        }
    }
}