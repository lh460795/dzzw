<?php

namespace App\Console\Commands;


use App\Models\Project;
use App\Models\Unit;
use App\Service\ProjectService;
use Illuminate\Http\Request;
use App\Work\Workflow;
use Illuminate\Console\Command;

class ProjectRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'project 主表数据迁移';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    protected $projectservice;
    protected $_quest;

    public function __construct(Request $request ,ProjectService $projectService)
    {
        $this->projectservice = $projectService;
        $this->_quest = $request;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        config(['database.connections.tenant.database' => 'test']);
        \DB::purge('tenant');
        \DB::reconnect('tenant');

        \DB::connection('xgwh_online')->table('wh_project')->orderBy('id')->chunk(1000, function ($projectlist)  {
            $aa =[];
            $project =null;
            $data_work=[];
            foreach ($projectlist as $k => $v) {
                if($v->status == 11){
                    continue;//驳回的项目 跳过
                }
                //入库时间
                $op_time = \DB::connection('xgwh_online')->table('wh_project_record')
                    ->where(['pid'=>$v->id,'status'=>6])->value('op_time') ?? 0;
                //立项单位操作时间
                $lxdw_time = \DB::connection('xgwh_online')->table('wh_project_record')
                        ->where(['pid'=>$v->id,'status'=>0])->value('op_time') ?? 0;
                $aa['id'] = $v->id;
                $aa['pro_num'] = $v->pro_num;
                $aa['yl_num'] = $v->yl_num;
                $aa['pid'] = $v->pid;
                $aa['uid'] = $v->uid;
                $aa['units_id'] = $v->corp_id;
                $aa['units_dis'] = $v->corp_dis;
                $aa['units_type'] = $v->corp_type;
                $aa['units_area'] = $v->corp_area;
                $aa['year'] = $v->year;
                $aa['year_range'] = $v->year_range;
                $aa['is_year'] = $v->is_year;
                $aa['yid'] = $v->yid;
                $aa['bid'] = $v->bid;
                $aa['type'] = $v->type;
                $aa['pname'] = $v->pname;
                $aa['zhuban'] = $v->corp_id; //现在存 单位ID
                $aa['zhu_fuze'] = $v->zhu_fuze;
                $aa['xieban'] = Unit::getXieban($v->xieban); //现在存 单位ID
                $aa['xie_fuze'] = $v->xie_fuze;
                $aa['proof'] = $v->proof;
                $aa['money_stream'] = $v->money_stream;
                $aa['place_use'] = $v->place_use;
                $aa['target'] = $v->target;
                $aa['plan'] = $v->plan;
                $aa['lianxiren'] = $v->lianxiren;
                $aa['tianbiaoren'] = $v->tianbiaoren;
                $aa['tianbiao_date'] = $v->tianbiao_date;
                $aa['pro_status'] = $this->getProstatus($v->pro_status,$v->un_complete);
                //$aa['ac_status'] = $v->ac_status;
                $aa['pro_type'] = $v->pro_type;
                $aa['pro_area'] = $v->pro_area;
                $aa['is_new'] = $v->is_new;
                $aa['amount'] = $v->amount;
                $aa['amount_now'] = $v->amount_now;
                //$aa['again_status'] = $v->again_status;
                $aa['progress'] = $v->progress;
                $aa['is_party'] = $v->is_party;
                $aa['m_score'] = $v->m_score;
                $aa['advance_day'] = $v->advance_day;
                $aa['is_report'] = $v->is_sb;
                $aa['is_push'] = $v->is_push;
                $aa['county_pid'] = $v->x_pid; //县市区ID
                $aa['relation_id'] = $v->relation_id;
                $aa['status_flow'] = $this->getStatus($v->status);
                $aa['wf_id'] = $this->getWfId($v->fen_uid); //流程ID
                $aa['is_adjust'] = $this->getAdjust($v->pro_status,$v->ac_status);
                $aa['is_complete'] = $this->getComplete($v->pro_status,$v->ac_status,$v->un_complete);
                $aa['fen_uid'] = $this->getFenuid($v->fen_uid);
                //$aa['is_card'] = $v->type;
                $aa['uptime'] = $op_time; //项目入库时间 （市长通过时间）评分需要入库时间
                //dd($aa);
                $project = Project::create($aa);
                /**插入工作流 相关表**/
                $data_work = [
                    'wf_type' => 'project', //业务表
                    'wf_fid' => $project->id,//业务表主键ID
                    'wf_id' => $aa['wf_id'],//流程表主键id
                    'new_type' => '0',//紧急程度
                    'check_con' => '',//审核意见
                    'log_time'=>$lxdw_time
                ];
                //dd($data_work);
                //开启工作流 会吧status_flow 更新为1 下面在进行更新操作
                $flow = Workflow::startworkflow($data_work, $v->uid);

                if(in_array($aa['wf_id'],[1,5,6,9,10,12,13,15])){//市工作流ID + 县市区流程
                    //王世荣,邓道伟,张汉平,吴婕,张志敏,叶华(市分管),郭斌 系统默认审核第一步
                    $request = $this->_quest;
                    $request['pid'] = $project->id;
                    $request['wf_type'] = 'project';
                    $request['check_con'] = 'default'; //系统默认审核
                    $request['uid'] =$project->uid;

                    $result = $this->projectservice->checkProject($request);
                }
                if ($flow['code'] != 1) {//工作流返回
                    echo '项目ID:'.$v->id.'插入工作流有问题！'."<br/>";
                }
            }

//            foreach ($projectlist as $k2 => $v2) {
//                //更新status_flow
//                $save['status_flow'] = $this->getStatus($v2->status);
//                Project::where('id',$v2->id)->update($save);
//            }
        });
    }

    //更新 wf_id
    public function getWfId($fen_uid){
        switch ($fen_uid) {
            case 16://邓道伟
                return 5;
                break;
            case 28://邓道伟
                return 5;
                break;
            case 17://郭斌
                return 13;
                break;
            case 18://王世荣
                return 1;
                break;
            case 19://张志敏
                return 10;
                break;
            case 20://吴婕
                return 9;
                break;
            case 21://叶华(市分管)
                return 12;
                break;
            case 32://张汉平
                return 6;
                break;
            case 34://叶华(市牵头)
                return 11;
                break;
            case 35://周先来
                return 2;
                break;
            case 36://陈剑
                return 3;
                break;
            case 37://李端阳
                return 4;
                break;
            case 38://彭桃安
                return 7;
                break;
            case 39://刘有年
                return 8;
                break;
            case 40://武荣楚
                return 14;
                break;
            case 0://县市区项目
                return 15;
                break;
            default:
                return 0;
                break;
        }
    }

    //更新 fen_uid
    public function getFenuid($fen_uid){
        //原字段存的是角色id  转换成uid
        if($fen_uid ==16 || $fen_uid ==28){
            $fen_uid = 28; //2个合并成一个
        }
        $user_id = \DB::connection('xgwh_online')->table('wh_user')
                ->where(['group_id'=>$fen_uid])->value('id') ?? 0;
        return $user_id;
    }

    //更新 status_flow
    public function getStatus($status){
        if($status == 6){
            return 2;//入库
        }elseif($status == 11){
            return -1;//驳回
        }else{
            return 1;//流程中
        }
    }

    //更新 is_adjust
    public function getAdjust($pro_status,$ac_status){
        if($pro_status ==5 || $ac_status ==1){
            return 2;// 停建，调整过的项目都给2
        }else{
            return 0;//
        }
    }
    //更新pro_status
    public function getProstatus($pro_status,$un_complete){
        if($un_complete ==7){
            return 6;// 未完结状态
        }else{
            return $pro_status;//
        }
    }
    //更新is_complete
    public function getComplete($pro_status,$ac_status,$un_complete){
        if($pro_status ==4 || $ac_status ==2 ){
            return 1;// 完结
        }elseif($un_complete ==7 || $ac_status ==3 ){
            return 2;//未完结
        }else{
            return 0;
        }
    }
}
