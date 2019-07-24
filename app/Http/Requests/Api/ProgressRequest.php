<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Progress;
use App\Models\ProjectPlanCustom;
use App\Models\Project;

class ProgressRequest extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    public function __construct()
    {
//        $res = request()->all();
//        if (!empty($res['data'])) {
//
//            $this->data = json_decode($res['data'], true);
//
//        }

    }

    public function rules()
    {
        $path = request()->path();
        $result = explode('/', $path);
        $actions = end($result);

        if ($actions == 'progressCount' || $actions == 'progressDai') {
            $reture = [];
            return $reture;
        }

        $type = request()->method();
        $res = request()->all();
        $data = json_decode($res['data'], true);

        if ($actions == 'write') {
            $cid = $data['cid'] ?? "0";
            $month = $data['month'] ?? "0";
            if (empty($cid)) {
                $reture['cid'] = 'required';
            } elseif (empty($month)) {
                $reture['month'] = 'required';
            } else {
                $reture = [];
            }


            return $reture;

        } else if ($actions == 'add') {
            //判断提交的p_status提交的值和是否比原来填写的还小
            $p_status = $data['p_status'];

            //节点id
            $custom_id = $data['cid'] ?? '0';
            //主体工程节点金额
            $m_account = $data['account'];
            //填写实际进度
            $a_progress = $data['a_progress'];
            //未完成原因
            $explain = $data['explain'];
            //备注
            $remarks = $data['remarks'];
            //判断是否需要填协办标识
            $xieban_flag = $data['xieban_flag'];

            $xieban = $data['corplist'];

            //协办单位id
            $units_ids = request()->input('units');
            //协办单位打分
            $rg_scores = request()->input('rg_score');
            //协办打分备注
            $remark = request()->input('remark');

            //判断提交的进度值
            if ($p_status) {
                $reture['status_v'] = $this->p_status($p_status);
            }

            //判断是否需要提交主体工程节点金额
            if (!$m_account) {
                $reture['account'] = $this->typeValue($custom_id);
            }

            //填写实际进度
            if (empty($a_progress)) {
                $reture['a_progress'] = 'required';
            }

            //填写实际进度的字数限制
            if (!empty($a_progress) && strlen(trim($a_progress)) < 150) {
                $reture['a_progress'] = 'min:50';
            }

            //未完成，请填写未完成原因
            if ($p_status != '4' && empty($explain)) {
                if ($p_status != '99') {
                    $reture['explain'] = 'required';
                }
            }

            //未完成原因不少于30字
            if (!empty($explain) && strlen(trim($explain)) < 90 && $p_status != '4') {
                $reture['explain'] = 'min:30';
            }
            //判断是否填协办和协办建议改进
            if ($xieban_flag == '1') {
                foreach ($xieban as $k => $v) {
                    if ($v['rg_score'] == '') {
                        //协办得分不能为空
                        $reture['rg_score'] = 'required';
                    }
                    if ($v['rg_score'] != 100 && $v['remark'] == '') {
                        //请在备注中填写扣分点及需改进的建议
                        $reture['remark'] = 'required';
                    }
                }

            }
            return $reture;

        } else if ($actions == 'show') {
            $cid = $data['cid'] ?? '0';
            $month = $data['month'] ?? '0';
            if (empty($cid)) {
                $reture['cid'] = 'required';
            } else if (empty($month)) {
                $reture['month'] = 'required';
            } else {
                $reture = [];
            }
            return $reture;

        } else if ($actions == 'pass') {
            $progress_id = $data['progress_id'];

            $progress_val = $this->progress($progress_id);
            if (!$progress_val) {
                $reture['progress_val'] = 'required';
            }

            if (empty($progress_id) && $progress_id != 0) {
                $reture['progress_id'] = 'required';
            } else {
                $reture[] = '';
            }

            return $reture;

        } else if ($actions == 'edit') {
            if ($type == 'GET') {
                $res = request()->all();
                $data = json_decode($res['data'], true);
                $progress_id = $data['progress_id'] ?? '0';
                if ($progress_id) {
                    $reture = [

                    ];
                } else {
                    $reture['progress_id'] = 'required';
                }
            } else if ($type == 'POST') {

                //判断提交的 progress_id
                $progress_id = $data['progress_id'] ?? '0';
                $progress_val = $this->progress($progress_id);

                if (!$progress_val) {
                    $reture['progress_val'] = 'required';
                }

                //判断提交的p_status提交的值和是否比原来填写的还小
                $p_status = $data['p_status'] ?? '0';
                //判断提交的进度值
                if ($p_status) {
                    $reture['status_v'] = $this->pro_status($p_status);
                }

                //节点id
                //主体工程节点金额
                $m_account = $data['account'] ?? '0';

                //判断是否需要提交主体工程节点金额
                if (!$m_account) {
                    $reture['account'] = $this->pro_typeValue($progress_id);
                }

                //填写实际进度
                $a_progress = $data['a_progress'] ?? '';
                //未完成原因
                $explain = $data['explain'] ?? '';
                //备注
                $remarks = $data['remarks'] ?? '';
                //判断是否需要填协办标识
                $xieban_flag = $data['xieban_flag'] ?? '';
                $xieban = $data['corplist'] ?? '';

                //协办单位id
                $units_ids = request()->input('units');
                //协办单位打分
                $rg_scores = request()->input('rg_score');
                //协办打分备注
                $remark = request()->input('remark');

                //判断提交的progress_id
                if (empty($progress_id)) {
                    $reture['progress_id'] = 'required';
                }

                //填写实际进度
                if (empty($a_progress)) {
                    $reture['a_progress'] = 'required';
                }

                //填写实际进度的字数限制
                if (!empty($a_progress) && strlen(trim($a_progress)) < 150) {
                    $reture['a_progress'] = 'min:50';
                }

                //未完成，请填写未完成原因
                if ($p_status != '4' && empty($explain)) {
                    if ($p_status != '99') {
                        $reture['explain'] = 'required';
                    }
                }

                //未完成原因不少于30字
                if (!empty($explain) && strlen(trim($explain)) < 90 && $p_status != '4') {
                    $reture['explain'] = 'min:30';
                }

                $corplist = $data['corplist'] ?? '';
                //判断是否填协办和协办建议改进
                if ($corplist) {
                    foreach ($xieban as $k => $v) {
                        if ($v['rg_score'] == '') {
                            //协办得分不能为空
                            $reture['rg_score'] = 'required';
                        }
                        if ($v['rg_score'] != 100 && $v['remark'] == '') {
                            //请在备注中填写扣分点及需改进的建议
                            $reture['remark'] = 'required';
                        }
                    }

                }
                return $reture;

            } else {
                $reture = [

                ];
            }
            return $reture;

        } else if ($actions == 'listpass') {
            if ($type == 'GET') {
                if ($data['role_id'] == '') {
                    $reture['role_id'] = 'required';
                } else if ($data['units_id'] == '') {
                    $reture['units_id'] = 'required';
                } else {
                    $reture = [

                    ];
                }

            } else if ($type == 'POST') {
                $pid = $data;
                if (empty($pid)) {
                    $reture['project_id'] = 'required';
                } else {
                    $reture = [

                    ];
                }

            } else {
                $reture = [

                ];
            }

            return $reture;

        } else if ($actions == 'progressJidu') {
            $pid = $data['pid'] ?? "0";
            if (empty($pid)) {
                $reture['pid'] = 'required';
            } else {
                $reture = [

                ];
            }
            return $reture;

        } else {
            $reture['cid'] = 'required';
            $reture['month'] = 'required';
            return $reture;
        }
    }

    //修改后比对填写大小
    public function pro_status($p_status)
    {
        $res = request()->all();
        $data = json_decode($res['data'], true);

        $progress_id = $data['progress_id'];
        $progress_res = Progress::where(['id' => $progress_id])
            ->orderBy('p_time', 'desc')
            ->first();

        //判断提交填写的进度值是否合法
        if (!empty($progress_res)) {
            if (intval($p_status) < intval($progress_res['p_status'])) {
                $reture['status_v'] = 'required';
            } else {
                $reture = '';
            }
        } else {
            $reture = '';
        }
        return $reture;
    }

    //对比修改后是否需要填写 account
    public function pro_typeValue($progress_id)
    {

        //找到对应节点的id
        $progress_res = Progress::where('id', $progress_id)->select('custom_id', 'pid')->first(); //节点自增ID
        $pro_info_m_value = ProjectPlanCustom::where('id', $progress_res['custom_id'])->select('m_value')->first(); //节点自增ID
        $pro_info_type = Project::where('id', $progress_res['pid'])->select('type')->first();

        if ($pro_info_type['type'] == '1' && trim($pro_info_m_value['m_value']) == "主体工程") {
            $reture['account'] = 'required';
        } else {
            $reture = '';
        }
        return $reture;
        //找到主表的类型
    }

    //比对填写进度值的大小
    public function p_status($p_status)
    {
        $res = request()->all();
        $data = json_decode($res['data'], true);
        //获取相关的查找条件
        $pid = $data['pid'];
        $cid = $data['cid'];
        $month = $data['month'];
        $progress_res = Progress::where(['pid' => $pid, 'custom_id' => $cid, 'month' => $month])->where('p_status', '!=', 5)
            ->orderBy('p_time', 'desc')
            ->first();

        //判断提交填写的进度值是否合法
        if (!empty($progress_res)) {
            if (intval($p_status) < intval($progress_res['p_status'])) {
                $reture['status_v'] = 'required';
            } else {
                $reture = '';
            }
        } else {
            $reture = '';
        }
        return $reture;

    }

    //判断类型和是否提交投资金额的进度值
    public function typeValue($custom_id)
    {
        $pro_info_m_value = ProjectPlanCustom::where('id', $custom_id)->select('m_value')->first(); //节点自增ID
        $pro_info_type = Project::where('id', $custom_id)->select('type')->first();
        if ($pro_info_type['type'] == '1' && trim($pro_info_m_value['m_value']) == "主体工程") {
            if (empty($data['m_account'])) {
                $reture['account'] = 'required';
            } else {
                $reture = '';
            }
        } else {
            $reture = '';
//            $reture = [
//
//            ];
        }
        return $reture;
    }

    //判断该节点是否存在
    public function progress($progress_id)
    {
        $res_cid = Progress::where('id', $progress_id)->value('custom_id');
        return $res_cid;
    }

    public function attributes()
    {
        return [
            'explain' => '未完成原因',
        ];
    }

    public function messages()
    {
        return [
            'cid.required' => '节点id不能为空',
            'month.required' => '月份不能为空',
            'p_status.required' => '请勾选进度',
            'status_v.required' => '勾选的进度百分比不能小于上次进度百分比',
            'account.required' => '投资金额未填写',
            'a_progress.required' => '请填写实际进度',
            'a_progress_length.required' => '实际进度不能少于50字',
            'explain.required' => '进度未完成，请填写未完成原因',
//            'explain_length.required' => '未完成原因不少于30字',
            'progress_id.required' => '进度id不能为空',
            'progress_val.required' => '该进度不存在',
            'rg_score.required' => '协办得分不能为空',
            'remark.required' => '请在备注中填写扣分点及需改进的建议',
            'role_id.required' => '人物组id不能为空',
            'units_id.required' => '人物单位id不能为空',
            'project_id.required' => '项目id不能为空',
        ];
    }
}