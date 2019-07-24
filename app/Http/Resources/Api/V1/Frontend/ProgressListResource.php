<?php
/**
 * 项目列表处理
 */

namespace App\Http\Resources\Api\V1\Frontend;

use App\Http\Resources\BaseResource;
use App\Work\Model\Run;
use App\Work\Repositories\ProcessRepo;

class ProgressListResource extends BaseResource
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
            //"type"=>\App\Models\Type::find($this->type)->name,
            "pname" => $this->pname,
            //"is_year"=>($this->is_year==0) ? '' :'跨年',
            //"year"=>$this->year,
            //"score"=>$this->m_score ?? '',
            //"progress"=>$this->progress,
            "pro_status" => get_pro_status($this->pro_status),
            //"wf_id"=>$this->wf_id,
            "fen_name" => \App\Models\User::find($this->fen_uid)->username ?? '暂无',
            //"status_name"=>$this->getStatusName($this->wf_id,$this->status_flow,$this->id),
            "tianbiao_time" => date('Y-m-d H:i:s', $this->tianbiao_date),
//            'custom_name'=>$this->custom_id,
            'type' => get_pro_kind($this->type),
            'year' => $this->year,
            'is_year' => $this->is_year,
            'progress' => $this->progress,
            'progressWrite' => $this->getProgress($this->progressWrite),

        ]);
    }


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
