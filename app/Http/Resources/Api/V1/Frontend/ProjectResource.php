<?php
/**
 * 项目详细页处理
 */
namespace App\Http\Resources\Api\V1\Frontend;

use App\Http\Resources\BaseResource;
use App\Models\Upload;

class ProjectResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->filterFields([

//            'units'=>\App\Models\Unit::getAll(),
            'nowtime'=>date('Y-m-d', time()),
            'form'=>[
                'id'=>$this->id,
                "pro_num" => $this->pro_num,
                "yl_num" => $this->yl_num,
                "units_id" => $this->units_id,
                "units_dis" => $this->units_dis,
                "units_type" => $this->units_type,
                "units_area" => $this->units_area,
                "year" => $this->year,
                "year_range" => $this->year_range,
                "is_year" => $this->is_year,
                "yid" => $this->yid,
                "bid" => $this->bid,
                "pname" => $this->pname,
                "zhuban" => [
                    'name'=>\App\Models\Unit::find($this->zhuban)->name,
                    'value'=>(int)$this->zhuban,
                    'alias_name'=>\App\Models\Unit::find($this->zhuban)->alias_name,
                ],
                "zhu_fuze" => $this->zhu_fuze,
                "xieban" => get_xieban_list($this->xieban,$this->xie_fuze),
                //"xie_fuze" => $this->xie_fuze,
                "proof" => $this->proof,
                "money_stream" => $this->money_stream,
                "place_use" => $this->place_use,
                "target" => $this->target,
                "plan" => $this->plan,
                "lianxiren" => $this->lianxiren,
                "tianbiaoren" => $this->tianbiaoren,
                "tianbiao_date" => $this->tianbiao_date,
                "status" => $this->status,
                "pro_status" => $this->pro_status,
                "ac_status" => $this->ac_status,
                "pro_type" =>[
                    'name'=>get_pro_type($this->pro_type),
                    'value'=>$this->pro_type
                ],
                "pro_area" => [
                    'name'=>\App\Models\Area::find($this->pro_area)->aname,
                    'value'=>$this->pro_area
                ],
                "is_new" => $this->is_new,
                "amount" => $this->amount,
                "amount_now" => $this->amount_now,
                "again_status" => $this->again_status,
                "progress" => $this->progress,
                "is_party" => $this->is_party,
                "m_score" => $this->m_score,
                "advance_day" => $this->advance_day,
                "is_report" => $this->is_report,
                "is_push" => $this->is_push,
                "un_complete" => $this->un_complete,
                "county_pid" => $this->county_pid,
                "relation_id" => $this->relation_id,
                "uptime" => $this->uptime,
                "status_flow" => $this->status_flow,
                "is_card" => $this->is_card,
                "wf_id" => [
                    'name'=>\App\Work\Model\Flow::find($this->wf_id)->flow_name,
                    'value'=>$this->wf_id
                ],
                'type'=>[
                    'name'=>\App\Models\Type::find($this->type)->name,
                    'value'=>$this->type
                ],
                'fenguanyiian'=>$this->yijian('fenguan'),//分管意见
                'leaderyijian'=>$this->yijian('leader')//领导意见
            ],
            //'plan' => $this->plancustom,
            'runlog' =>  RunLogResource::collection($this->whenLoaded('runlog')),//嵌套runlog资源集合
            'flow_count'=>$this->flow_count, //关注项目数量
            'is_flow'=>$this->is_flow, //当前用户是否关注
            'fileList'=>Upload::where(['pid'=>$this->id,'relation_id'=>$this->id,'file_type'=>1])->get()
        ]);
    }

    //返回分管意见
    public function yijian($type){
        $runlog = RunLogResource::collection($this->whenLoaded('runlog'));
        //dd($runlog->resource->toArray());
        $runlog_info  = collect($runlog->resource)->toArray();
        //dd($runlog_info);
        //前端小妹要默认值
        $response_fenguan = [
            'username'=>'',
            'rolename'=>'',
            'content'=>'',
            'created_at'=>''
        ];
        $response_leader = [
            0=>[
                'username'=>'',
                'rolename'=>'',
                'content'=>'',
                'created_at'=>''
            ],
            1=>[
                'username'=>'',
                'rolename'=>'',
                'content'=>'',
                'created_at'=>''
            ]
        ];
        if(!empty($runlog_info)){
            foreach ($runlog_info as $key=>$item){
                if($type =='fenguan'){
                    if($item['rolename'] =='分管副市长'){
                        $response_fenguan['username'] = $item['username'];
                        $response_fenguan['rolename'] = $item['rolename'];
                        $response_fenguan['content'] = $item['content'];
                        $response_fenguan['created_at'] = date('Y年m月d日',strtotime($item['created_at']));
                    }
                }else{
                    if($item['rolename'] =='市长'){
                        $response_leader[0]['username'] = $item['username'];
                        $response_leader[0]['rolename'] = $item['rolename'];
                        $response_leader[0]['content'] = $item['content'];
                        $response_leader[0]['created_at'] = date('Y年m月d日',strtotime($item['created_at']));
                    }
                    if($item['rolename'] =='常务副市长'){
                        $response_leader[1]['username'] = $item['username'];
                        $response_leader[1]['rolename'] = $item['rolename'];
                        $response_leader[1]['content'] = $item['content'];
                        $response_leader[1]['created_at'] = date('Y年m月d日',strtotime($item['created_at']));
                    }

                }
            }
        }
        if($type =='fenguan'){
            return $response_fenguan;
        }else{
            return $response_leader;
        }
        //dd($response_fenguan);
    }

}
