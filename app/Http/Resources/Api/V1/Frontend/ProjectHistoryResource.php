<?php

namespace App\Http\Resources\Api\V1\Frontend;

use App\Http\Resources\BaseResource;
use App\Models\ProjectHistory;
use App\Models\ProjectProgressHistory;

class ProjectHistoryResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     * 历史记录，查看编辑前的项目
     * @param  \Illuminate\Http\Request  $request
     * @return array  
     */
    public function toArray($request)
    {
        return [
            'nowtime' => date('Y-m-d', time()),
            'form' => [
                "id" => $this->id,
                "pro_num" => $this->pro_num,
                "yl_num" => $this->yl_num,
                "units_id" => $this->units_id,
                "units_name" => \App\Models\Unit::find($this->units_id)->alias_name,
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
                "wf_id" => [
                    'name'=>\App\Work\Model\Flow::find($this->wf_id)->flow_name,
                    'value'=>$this->wf_id
                ],
                'type'=>[
                    'name'=>\App\Models\Type::find($this->type)->name,
                    'value'=>$this->type
                ],
                'flag' => $this->flag,
                'plan' => $this->plancustom($this->id, $this->flag),
            ],
        ];
    }

    public function plancustom($pid, $flag){
        $project_info = ProjectHistory::with(['planCustomHistory'=>function($query)use($flag){
            $query->where('flag', $flag);
        }])->find($pid);
        // dd($project_info);
        $plancustom =$this->getPlancustomHistory($project_info->planCustomHistory);
        //dd($plancustom);
        return $plancustom;
    }

    //项目详细页 转换 plancustom
    public function getPlancustomHistory($plan,$xieban=0){
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
                        $progress_list = ProjectProgressHistory::select('id','p_status','month','y_time','custom_id','pid','p_year')
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
                        $progress_info = ProjectProgressHistory::
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
                            $xt_info = ProjectProgressHistory::
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
}
