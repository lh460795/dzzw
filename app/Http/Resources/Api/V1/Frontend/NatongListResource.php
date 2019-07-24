<?php
/**
 * 项目列表处理
 */

namespace App\Http\Resources\Api\V1\Frontend;

use App\Http\Resources\BaseResource;
use App\Work\Model\Run;
use App\Work\Repositories\ProcessRepo;
use App\Models\ProjectPlanCustom;

class NatongListResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {

        return $this->filterFields([
            "id" => $this->id,
            "projectType" => \App\Models\Type::find($this->projectType)->name,
            "createProjecUnit" => getUnits($this->createProjecUnit),
            "projectName" => $this->projectName,
            "projectStatus" => $this->projectStatus,
            "projectProgress" => $this->projectProgress,
            //"is_year"=>($this->is_year==0) ? '' :'跨年',
            //"year"=>$this->year,
            //"score"=>$this->m_score ?? '',
            //"progress"=>$this->progress,
//            "pro_status" => get_pro_status($this->pro_status),
            //"wf_id"=>$this->wf_id,
//            "fen_name" => \App\Models\User::find($this->fen_uid)->username ?? '暂无',
            //"status_name"=>$this->getStatusName($this->wf_id,$this->status_flow,$this->id),
//            "tianbiao_time" => date('Y-m-d H:i:s', $this->tianbiao_date),
//            'custom_name'=>$this->custom_id,
//            'type' => get_pro_kind($this->type),
//            'year' => $this->year,
//            'is_year' => $this->is_year,
//            'progress' => $this->progress,
//            'progressWrite' => $this->getProgress($this->progressWrite),
            'tagId' => $this->natong['is_incor'],
            'tagReason' => $this->natong['natong_reason'],
            'natong_number' => $this->natongNumber($this->natong),
            'tagDate' => $this->getNatongTime($this->natong_record),
            'NaTongRecord' => $this->getNatong($this->natong_record),
            'month_id' => $this->getMonth($this->id),
        ]);
    }

    public function getMonth($id)
    {
        $custom_month = ProjectPlanCustom::where(['pid' => $id, 'm_value' => '主体工程'])->where('p_month', '!=', '')->value('p_month');
        if ($custom_month) {
            $month_id = intval(substr($custom_month, 0, 1));
        } else {
            $month_id = 0;
        }
        return $month_id;

    }

    public function natongNumber($natong)
    {
        $res_natong = $natong->toArray();
        if ($res_natong['natong_number']) {
            $natong_number[] = $res_natong['natong_number'] ?? "";
        } else if ($res_natong['natong_number2']) {
            $natong_number[] = $res_natong['natong_number2'] ?? "";
        } else if ($res_natong['natong_number3']) {
            $natong_number[] = $res_natong['natong_number3'] ?? "";
        } else if ($res_natong['natong_number4']) {
            $natong_number[] = $res_natong['natong_number4'] ?? "";
        } else if ($res_natong['natong_number5']) {
            $natong_number[] = $res_natong['natong_number5'] ?? "";
        } else if ($res_natong['natong_number6']) {
            $natong_number[] = $res_natong['natong_number6'] ?? "";
        }

        if (!empty($natong_number)) {
            return $natong_number;
        }

    }

    public function getNatong($natong_record)
    {
        $new_record = array();
        if ($natong_record) {
            foreach ($natong_record as $k => $v) {
                $new_record[$k]['id'] = $v['id'];
                $new_record[$k]['name'] = \App\Models\User::find($v['uid'])->username ?? "系统";
                $new_record[$k]['date'] = date('Y-m-d H:i:s', $v['edit_time']);
                $new_record[$k]['NaTongStatus'] = getNatongStatus($v['natong_status']);
            }
            return $new_record;
        } else {
            $new_record = [];
            return $new_record;
        }
    }

    //最后纳统时间
    public function getNatongTime($natong_record)
    {
        if (!empty($natong_record)) {
            $natongTime = $natong_record->toArray();
            if ($natongTime) {
                $end_time = end($natongTime);
                $data_time = date('Y-m-d H:i:s', $end_time['edit_time']);
                return $data_time;
            } else {
                return '2019-03-25 10:40:45';
            }
        } else {
            return '2019-03-25 10:40:45';
        }
    }

    /**
     * @param $progressWrite
     * @return array获取纳统状态
     */


    public function getProgress($progressWrite)
    {
        if ($progressWrite) {
            $new_progress['progress_id'] = $progressWrite['id'];
            $new_progress['pid'] = $progressWrite['pid'];
            $new_progress['update_time'] = date('Y-m-d H:i:s', $progressWrite['p_time']);
            $new_progress['p_progress'] = stristr($progressWrite['p_progress'], '：');
        } else {
            $new_progress = [];
        }

        //dd($new_progress);
        return $new_progress;
    }

    /**
     * Notes:根据项目状态ID 项目id 业务类型 获取当前项目状态
     * Date: 2019-06-28
     * Time: 16:58
     * @param $wf_id 工作流ID
     * @param $status_flow 项目工作流状态
     * @param $pid 项目id
     * @param string $wf_type 业务表类型
     * @return string
     */
    public function getStatusName($wf_id, $status_flow, $pid, $wf_type = 'project')
    {
        $status_name = '';
        if ($status_flow == 2) {
            $status_name = '在建中';
        } elseif ($status_flow == -1) {
            $info = Run::where([['from_table', '=', $wf_type], ['from_id', '=', $pid], ['status', '=', 1]])
                ->select('id', 'run_flow_process')->first();
            $process = ProcessRepo::getProcessInfo($info['run_flow_process']);//当前步骤
            $status_name = $process->process_name . '驳回' ?? '驳回';
        } else {
            $info = Run::where([['from_table', '=', $wf_type], ['from_id', '=', $pid], ['status', '=', 0]])
                ->select('id', 'run_flow_process')->first();
            $process = ProcessRepo::getProcessInfo($info['run_flow_process']);//当前步骤
            //$nexprocess = ProcessRepo::getNexProcessInfo($wf_type,$pid,$info['run_flow_process']);//下一步骤
            //$preprocess = ProcessRepo::getPreProcessInfo($info['id']);//之前步骤
            $status_name = '待' . $process->process_name . '审核' ?? '待审核';
        }
        return $status_name;
    }
}
