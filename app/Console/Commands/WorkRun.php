<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Service\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Console\Command;

class WorkRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'work:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'project 操作日志表数据迁移';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    private $_projectService;
    private $_quest;

    public function __construct(Request $request ,ProjectService $projectService)
    {
        $this->_projectService = $projectService;
        $this->_quest = $request;
        //冻结项目 780, 1027, 184
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

        $where= [
            ['p.status','!=',11]
//            ['p.id','!=',Project::DJ_1],
//            ['p.id','!=',Project::DJ_2],
//            ['p.id','!=',Project::DJ_3],
            //['r.status','!=',0] //排除立项单位 操作记录  项目入库时已经迁移
        ];
        \DB::connection('xgwh_online')->table('wh_project_record as r')
            ->rightJoin('wh_project as p', 'p.id', '=', 'r.pid')
            ->where($where)
            ->selectRaw('r.*,p.is_party')
            ->orderBy('r.pid')->chunk(10000, function ($projectlist)  {
            $aa =[];
            //$save = $this->_quest;
            $data = $this->_quest;
            //dd(1);
            foreach ($projectlist as $k => $v) {
                //303 吴庆恒 政府投资类
                $result_1 = \DB::connection('xgwh_online')->table('wh_project_record')
                    ->where([
                        ['pid' ,'=', $v->pid],
                        ['uid' ,'=', 303]
                    ])->value('id');

                //309 程想军 招商引资
                $result_2 = \DB::connection('xgwh_online')->table('wh_project_record')
                    ->where([
                        ['pid' ,'=', $v->pid],
                        ['uid' ,'=', 309]
                    ])->value('id');

                $data['check_con'] = '';
                $data['wf_type'] = 'project';

                if((!$result_1) && ($v->type ==1)){ //政府投资类 并且 不存在303 吴庆恒 操作记录时
                    if($v->status ==0){
                        //插入default 流程
                        //$this->defaultFlow($this->getWfId($v->fen_uid),$v->pid,$v->uid);
                        //手动插入操作日志
                        $data['check_con'] = '';
                        $data['uid'] =303;
                        $data['pid'] = $v->pid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==1){//科室审核
                        $data['check_con'] = '';
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==2){//副秘书长审核
                        $data['check_con'] = '';
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==3){//分管副市长审核
                        $data['check_con'] = $v->fenguan;
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==4){//“五化”办审核
                        $data['check_con'] = '';
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==5){//常务副市长审核
                        if($v->is_party ==1){
                            //市委项目
                            $data['check_con'] = $v->fenguan;
                        }else{
                            $data['check_con'] = $v->wuhua;
                        }
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==6){//市长审核
                        $data['check_con'] = $v->shizhang;
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }
                }elseif((!$result_2) && ($v->type ==2)){ // 不存在309 程想军 招商引资
                    if($v->status ==0){
                        //插入default 流程
                        //$this->defaultFlow($this->getWfId($v->fen_uid),$v->pid,$v->uid);
                        //手动插入操作日志
                        $data['check_con'] = '';
                        $data['uid'] =309;
                        $data['pid'] = $v->pid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==1){//科室审核
                        $data['check_con'] = '';
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==2){//副秘书长审核
                        $data['check_con'] = '';
                        $data['uid'] =$v->uid;
                        $data['pid'] = $v->pid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==3){//分管副市长审核
                        $data['check_con'] = $v->fenguan;
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==4){//“五化”办审核
                        $data['check_con'] = '';
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==5){//常务副市长审核
                        if($v->is_party ==1){
                            //市委项目
                            $data['check_con'] = $v->fenguan;
                        }else{
                            $data['check_con'] = $v->wuhua;
                        }
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==6){//市长审核
                        $data['check_con'] = $v->shizhang;
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }
                }elseif ($result_1 || $result_2){ //如果存在 303 吴庆恒  309 程想军
                    if($v->status ==9){
                        //插入default 流程
                        //$this->defaultFlow($this->getWfId($v->fen_uid),$v->pid,$v->uid);
                    }elseif($v->status ==0){// 303 吴庆恒  309 程想军
                        $data['check_con'] = $v->twoshenhe;
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==1){//科室审核
                        $data['check_con'] = '';
                        $data['uid'] =$v->uid;
                        $data['pid'] = $v->pid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==2){//副秘书长审核
                        $data['check_con'] = '';
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==3){//分管副市长审核
                        $data['check_con'] = $v->fenguan;
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==4){//“五化”办审核
                        $data['check_con'] = '';
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==5){//常务副市长审核
                        if($v->is_party ==1){
                            //市委项目
                            $data['check_con'] = $v->fenguan;
                        }else{
                            $data['check_con'] = $v->wuhua;
                        }
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==6){//市长审核
                        $data['check_con'] = $v->shizhang;
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }
                }elseif($v->type >2){ //深化改革类 其他类
                    if($v->status ==0){
                        //插入default 流程
                        //$this->defaultFlow($this->getWfId($v->fen_uid),$v->pid,$v->uid);
                    }elseif($v->status ==1){//科室审核
                        $data['check_con'] = '';
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==2){//副秘书长审核
                        $data['check_con'] = '';
                        $data['uid'] =$v->uid;
                        $data['pid'] = $v->pid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==3){//分管副市长审核
                        if($v->fen_uid ==0){
                            //如果是县市区项目  插入default 流程
                            //$this->defaultFlow($this->getWfId($v->fen_uid),$v->pid,$v->uid);
                            continue;
                        }elseif($v->is_party ==1){
                            //如果是县市区项目 市委项目 跳过此步
                            continue;
                        }else{
                            $data['check_con'] = $v->fenguan;
                            $data['pid'] = $v->pid;
                            $data['uid'] =$v->uid;
                            $data['log_time'] = $v->op_time;
                            $this->checkProject($data);
                        }
                    }elseif($v->status ==4){//“五化”办审核
                        $data['check_con'] = '';
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==5){//常务副市长审核
                        if($v->is_party ==1){
                            //市委项目
                            $data['check_con'] = $v->fenguan;
                        }else{
                            $data['check_con'] = $v->wuhua;
                        }
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }elseif($v->status ==6){//市长审核
                        $data['check_con'] = $v->shizhang;
                        $data['pid'] = $v->pid;
                        $data['uid'] =$v->uid;
                        $data['log_time'] = $v->op_time;
                        $this->checkProject($data);
                    }
                }

            }
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

    //更新 status_flow
    public function getStatus($status){
        if($status ==6){
            return 2;//入库
        }elseif($status ==11){
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
    //项目审核
    public function checkProject($data){
        //dump($data);exit;
        try{
            $result = $this->_projectService->checkProject($data);
        }catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    //默认审核
    public function defaultFlow($wf_id,$pid,$uid){
       if(in_array($wf_id,[1,5,6,9,10,12,13,15])){//市工作流ID 县市区流程
            //王世荣,邓道伟,张汉平,吴婕,张志敏,叶华(市分管),郭斌 系统默认审核第一步
            $request = $this->_quest;
            $request['pid'] = $pid;
            $request['wf_type'] = 'project';
            $request['check_con'] = 'default'; //系统默认审核
            $request['uid'] =$uid;

            $result = $this->_projectService->checkProject($request);
       }
    }
}
