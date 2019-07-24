<?php
/**
 * 工作流日志处理
 */
namespace App\Http\Resources\Api\V1\Frontend;

use App\Http\Resources\BaseResource;

class RunLogResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        $role = \App\Models\User::find($this->uid)->roles()->first()->display_name ?? '';
        $rolename = \App\Work\Model\FlowProcess::find($this->flow_process)->process_name??$role;

        return $this->filterFields([
//            "id"=>$this->id,
            "uid"=>$this->uid,
            "username"=>\App\Models\User::where('id',$this->uid)->value('real_name') ?? '',
            "rolename"=>$rolename,
//            "from_id"=>$this->from_id,
//            "from_table"=>$this->from_table,
//            "run_id"=>$this->run_id,
//            "run_flow"=>$this->run_flow,
            "content"=>$this->getContent($rolename,$this->content),
//            "dateline"=>$this->dateline,
            "btn"=>$this->getBtn($this->btn,$role),
//            "art"=>$this->art,
            "created_at"=>date('Y-m-d H:i:s',$this->dateline), //$this->created_at->format('Y-m-d H:i:s')
//            "updated_at"=>(string)$this->updated_at
        ]);
    }

    //返回操作日志 通过 驳回 送审
    protected function getBtn($btn,$role=''){
        $ok = '通过';
        if($role =='五化办'){
            $ok = '送审';
        }else{
            $ok = '通过';
        }
        $type = ['Send'=>'申报','ok'=>$ok,'Back'=>'驳回','SupEnd'=>'终止流程',
            'Sing'=>'会签提交','sok'=>'会签同意','SingBack'=>'会签退回','SingSing'=>'会签再会签'];
        return $type[$btn] ?? '';
    }
    //返回审核意见
    protected function getContent($rolename,$content){
        if(in_array($rolename,['科室','副秘书长','五化办']))
        {
            return '';
        }else{
            return $content;
        }
    }
}
