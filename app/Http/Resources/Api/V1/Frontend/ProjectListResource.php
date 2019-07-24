<?php
/**
 * 项目列表处理
 */

namespace App\Http\Resources\Api\V1\Frontend;

use App\Http\Resources\BaseResource;
use App\Work\Model\Run;
use App\Work\Repositories\ProcessRepo;

class ProjectListResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        //$role = \App\Models\User::find($this->uid)->roles()->first()->display_name;
        //$rolename = \App\Work\Model\FlowProcess::find($this->flow_process)->process_name??$role;

        return $this->filterFields([
            "id" => $this->id,
            //"units_id"=>$this->units_id,
            "units_name" => \App\Models\Unit::where('id',$this->units_id)->value('name') ?? '暂无',
            "type" => \App\Models\Type::where('id',$this->type)->value('name'),
            "pname" => $this->pname,
            "is_year" => ($this->is_year == 0) ? '' : '跨年',
            "year" => $this->year,
            "score" => $this->m_score ?? '',
            "progress" => $this->progress,
            "pro_status" => get_pro_status($this->pro_status),
            "pro_status_id" => $this->pro_status,
            //"wf_id"=>$this->wf_id,
            "fen_name" => \App\Models\User::where('id',$this->fen_uid)->value('username') ?? '暂无',
            "status_name" => $this->getStatusName($this->wf_id, $this->status_flow, $this->id),
            "created_at" => empty($this->created_at)?null:$this->created_at,
        ]);
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
            if($info){
                $process = ProcessRepo::getProcessInfo($info['run_flow_process']);//当前步骤
                $status_name = $process->process_name . '驳回' ?? '驳回';
            }else{
                $status_name = '';
            }
        } else {
            $info = Run::where([['from_table', '=', $wf_type], ['from_id', '=', $pid], ['status', '=', 0]])
                ->select('id', 'run_flow_process')->first();
            if($info){
                $process = ProcessRepo::getProcessInfo($info['run_flow_process']);//当前步骤
                //$nexprocess = ProcessRepo::getNexProcessInfo($wf_type,$pid,$info['run_flow_process']);//下一步骤
                //$preprocess = ProcessRepo::getPreProcessInfo($info['id']);//之前步骤
                $status_name = '待' . $process->process_name . '审核' ?? '待审核';
            }else{
                $status_name = '';
            }
        }
        return $status_name;
    }
}
