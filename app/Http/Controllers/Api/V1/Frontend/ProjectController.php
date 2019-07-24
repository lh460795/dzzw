<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Requests\Api\ProjectRequest;
use App\Http\Resources\Api\V1\Frontend\ProjectListCollection;
use App\Models\MonthScoreHistory;
use App\Models\Project;
use App\Models\ProjectPlanCustom;
use App\Models\User;
use App\Service\ProjectService;
use App\Models\Card;
use App\Service\ScoreService;
use App\Work\Workflow;
use App\Models\Upload;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\URL;
use PDF;
class ProjectController extends Controller
{
    protected $projectservice;

    public function __construct(ProjectService $projectService)
    {
        $this->projectservice = $projectService;
    }

    //项目详细页
    public function show(ProjectRequest $projectRequest){
        //dd(\App\Models\User::find(2)->roles()->first()->display_name);
        $pid = $projectRequest->input('id') ?? '';
        try{
            $project_info = $this->projectservice->getProjectInfo($pid);
            //dd($project_info);
            return $this->success($project_info);
        }catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }

    }

    //申报引导页面
    public function entry(){
        try{
            $result['wf_id'] = getFenguan(); //分管列表
            $result['year_range'] = getYear(date('Y', time())); //年份范围
            $result['area'] = getAreaAll();//地区列表
            $result['units']= \App\Models\Unit::getAll();
            $result['projectType'] =\App\Models\Type::select('id as value','name as label')->orderBy('sort','desc')->get()->toArray();
            return $this->success($result);
        }catch (\Exception $e) {
            return $this->failed('获取信息失败！');
        }
    }

    //项目标准模板表接口 引导页面第二步
    public function plan($type){
        try {
            $response = $this->projectservice->getPlaninfo($type);
            return $this->success($response);
        }catch (\Exception $e) {
            return $this->failed('获取信息失败！');
        }
    }

    //项目列表
    public function index(Request $request){
        try{
            $result = $this->projectservice->getProject($request);
            return $this->success($result);
        }catch (\Exception $e) {
            return $this->failed($e->getMessage());
        }
    }

    //驳回后更新项目 重新走工作流
    public function update(Request $request,$id){
        $user = Auth::guard('api')->user();
        $uid = $user->id ??1;
        //dd($uid);
        $project = Project::find($id);
        //驳回状态才能操作
        if($project->status_flow !='-1'){
            return $this->failed('无权限操作!');
        }
        $data = $request->all();
        $data_array = json_decode($data['data'],true);
        $project_form = $data_array['form'];//获取表单基础数据
        $fileList=json_decode($data['fileList']);  //附件
        //拼接数据 协办 按照旧数据格式
        $array_xieban = $project_form['cooprateCorps'];
        $project_form['xieban'] = $this->projectservice->dataFormatXieban($array_xieban)['xieban'];
        $project_form['xie_fuze'] = $this->projectservice->dataFormatXieban($array_xieban)['xie_fuze'];

        //手动创建验证 request类不支持json 参数
        //\Validator::make($project_form, $rules,$message)->validate();
        unset($project_form['tianbiao_date']);
        DB::beginTransaction();
        try {
            $project_info = $project->update($project_form);
            //获取计划节点数据
            $plan_form_create = $this->projectservice->arrayToplan($data_array['plan'],$project);

            $plan_custom_ids = ProjectPlanCustom::where('pid',$project->id)
                ->orderBy('id','asc')->pluck('id')->toArray(); //节点自增ID
//dd($plan_custom_ids);
            foreach ($plan_form_create as $k2=>$val){
                $save = $val; //插入或更新数据用
                unset($save['id']);
                $save['pid'] = $project->id;
                //如果id 存在数据库中 更新
                if(in_array($val['id'],$plan_custom_ids)){
                    ProjectPlanCustom::where('id',$val['id'])->update($save);//更新节点
                }else{
                    ProjectPlanCustom::create($save); //新增
                }

            }
            //附件上传
            Upload::where(['pid'=>$project->id,'relation_id'=>$project->id,'file_type'=>1])->delete();
            if(!empty($fileList))
            {
                Upload::upload($project->id,$project->id,$uid,1,$fileList);
            }
            $data_work = [
                'wf_type' => 'project', //业务表
                'wf_fid' => $project->id,//业务表主键ID
                'wf_id' => $project->wf_id,//流程表主键id
                'new_type' => '0',//紧急程度
                'check_con' => '',//审核意见
            ];
            //开启工作流
            $flow = Workflow::startworkflow($data_work, $uid);
            //dd($flow);
            if ($flow['code'] != 1) { //工作流返回
                DB::rollback();
                return $this->failed($flow);
            }else{
                $flow['pid'] = $project->id;//拼接项目ID
                DB::commit();
            }
            return $this->success($flow);
        }catch (\Exception $e) {
            DB::rollback();
            return $this->failed($e->getMessage());
        }
    }

    /*
     * 申报项目 ProjectRequest
     */
    public function store(Request $request){
        $user = Auth::guard('api')->user();
        $uid = $user->id ??1;
        //validate 验证
        $rules = [
            'pname' => ['required', 'max:60', 'unique:project,pname'],
            'uid' => ['required','int'],
            'type' => ['required','int'],
        ];

        $message = [
            'id.required'=>'用户ID必须填写',
            'id.exists'=>'用户不存在',
            'pname.unique' => '项目已经存在',
            'pname.required' => '项目不能为空',
            'uid.required' => '用户不能为空',
            'type.required' => '项目类型不能为空',
        ];

        $data = $request->all();
        $data_array = json_decode($data['data'],true);
        //$ret = json_last_error();
        //dump($ret);
        $project_form = $data_array['form'];//获取表单基础数据
        $fileList=(isset($data['fileList'])) ? json_decode($data['fileList'],true) : [];  //附件
        //'type','project'
        $flow_name = \App\Work\Model\Flow::where(['type'=>'project','id'=>$project_form['wf_id']])->value('uid');
        $project_form['fen_uid'] = $flow_name ?? 0;//分管uid
        //预留编号 +15位 规则编号
        $project_form['pro_num'] = $this->projectservice->getProjectNum($project_form,$user);

        $project_form['year'] = date('Y', time()); //当前年份
        $project_form['uid'] = $uid;
        $project_form['units_id'] = $user->units_id ?? 0;
        $project_form['units_dis'] = $user->unit->units_dis ?? 0;
        $project_form['units_type'] = $user->unit->units_type ?? 0;
        $project_form['units_area'] = $user->unit->units_area ?? 0;
        $project_form['tianbiao_date'] = time();

        //拼接数据 协办 按照旧数据格式
        $array_xieban = $project_form['cooprateCorps'];
        $project_form['xieban'] = $this->projectservice->dataFormatXieban($array_xieban)['xieban'];
        $project_form['xie_fuze'] = $this->projectservice->dataFormatXieban($array_xieban)['xie_fuze'];
        //$project_form['is_adjust'] = 1;//测试用
        //dd($project_form);

        //手动创建验证 request类不支持json 参数
        \Validator::make($project_form, $rules,$message)->validate();

        DB::beginTransaction();
        try {
            //dd($project_form);
            $project = Project::create($project_form);
            //获取计划节点数据
            $plan_form_create = $this->projectservice->arrayToplan($data_array['plan'],$project,1);
            $project_plan = ProjectPlanCustom::insert($plan_form_create); //插入节点表

            //附件
            if(!empty($fileList))
            {
                Upload::upload($project->id,$project->id,$uid,1,$fileList);
            }
            $data_work = [
                'wf_type' => 'project', //业务表
                'wf_fid' => $project->id,//业务表主键ID
                'wf_id' => $project_form['wf_id'],//流程表主键id
                'new_type' => '0',//紧急程度
                'check_con' => '',//审核意见
            ];
            //开启工作流
            $flow = Workflow::startworkflow($data_work, $uid);
            if ($flow['code'] != 1) { //工作流返回
                DB::rollback();
                return $this->failed($flow);
            }else{
                $flow['pid'] = $project->id;//拼接项目ID
                if(in_array($project->wf_id,[1,5,6,9,10,12,13,15])){//市工作流ID 县市区流程
                    //王世荣,邓道伟,张汉平,吴婕,张志敏,叶华(市分管),郭斌 系统默认审核第一步
                    $request['pid'] = $project->id;
                    $request['wf_type'] = 'project';
                    $request['check_con'] = 'default'; //系统默认审核
                    $request['uid'] =$project->uid;
                    
                    $result = $this->projectservice->checkProject($request);
                }
                DB::commit();
            }
            return $this->success($flow);
        }catch (\Exception $e) {
            DB::rollback();
            return $this->failed($e->getMessage());
        }
    }

    // 工作流审核
    public function wfcheck(Request $request){
//        $uid = Auth::guard('api')->user()->id ?? 1;
//        $role = Auth::guard('api')->user()->roles[0]->id ?? 1; //角色id
        try{
            $message = $this->projectservice->wfCheck($request);
            //先后台验证
            if ($message['code'] == '-1') {
                return $this->failed($message['msg']);
            }
            $result = $this->projectservice->checkProject($request);
            return $this->success($result);
        }catch (\Exception $e) {
            //dd($e->getMessage());
            return $this->failed('审核失败!');
        }

    }

    //项目节点内容 单独一个接口
    public function plancustom($pid){
        //dd(\App\Models\User::find(2)->roles()->first()->display_name);
        $rules = [
            'id' => ['required','int','exists:project,id']
        ];

        $message = [
            'id.required'=>'项目ID必须填写',
            'id.exists'=>'项目ID不存在',
        ];
        $valform['id'] = (int)$pid;
        //手动创建验证
        \Validator::make($valform, $rules,$message)->validate();

        try{
            $project_info = $this->projectservice->getPlans($pid);
            //dd($project_info);
            return $this->success($project_info);
        }catch (\Exception $e) {
            return $this->failed('获取失败！');
        }

    }
    /*
     * 删除
     */
    public function delete(){

    }


    /*
     * 协办单位项目列表
     * */
    public function xieban(Request $request)
    {
        $units_id=$request->get('units_id');
        $perPage = $request->get('per_page') ?? 10;  //每页显示条数
        $page = $request->get('page') ?? 1;  //当前页
        if(!$units_id)
        {
            return $this->failed('操作失败');
        }
        $res=ProjectPlanCustom::whereRaw('FIND_IN_SET(?,m_zrdw)', [$units_id])->get()->toArray();
        if(!$res)
        {
            return $this->failed('当前单位暂无协办项目');
        }
        foreach ($res as $k => $v) {
            $ress[$v['pid']][] = $v;
        }

        //用K值来查找出 主表的相关数据
        foreach ($ress as $k => $v) {
            $where_crop=[
                ['id', '=', $k],
                ['status_flow', '=', 2]  //通过
            ];
            $abc=Project::with('unit:id,name')
                ->where($where_crop)
                ->first();
            if($abc)
            {
                if($abc['units_id'] != $units_id)
                {
                    $res_pro['id']=$abc['id'];
                    $res_pro['pname']=$abc['pname'];
                    $res_pro['units_id']=$abc['units_id'];
                    $res_pro['units_name']=$abc->unit['name'];
                    $res_pro['plans'] = $v;
                    $res_pros[] = $res_pro;
                }
            }
        }

        if(empty($res_pros))
        {
            return $this->failed('当前单位暂无协办项目');
        }
        foreach ($res_pros as $k1=>$v1)
        {
            $res_pros[$k1]['plans']=$this->projectservice->getPlancustom($v1['plans'],1);

        }

        //计算每页分页的初始位置
        $offset = ($page * $perPage) - $perPage;
        //实例化LengthAwarePaginator类，并传入对应的参数
        $data = new LengthAwarePaginator(array_slice($res_pros, $offset, $perPage, true), count($res_pros), $perPage,
            $page, ['path' => $request->url(), 'query' => $request->query()]);
        return $this->success($data);
    }


    /*
     * 项目发牌
     */
    public function cardadd(Request $request)
    {
        $pid=$request->get('pid'); //项目id
        $color=$request->get('color')?? 1; //发牌颜色  默认黄牌
        $remark=$request->get('remark'); //发牌备注
        $uid=$request->get('uid'); //发牌人id
        $project = Project::find($pid);
        if(empty($project))
        {
            return $this->failed('项目不存在！');
        }
        if (empty($remark)) {
            return $this->failed('备注不能为空!');
        }
        //查询项目现在状态
        $pro_status = $project->pro_status;
        if ($pro_status == 2) {
            return $this->failed('此项目已经是红牌，不可再发牌!');
        } elseif ($pro_status == 1) {
            if ($color == 1) {
                return $this->failed('此项目已经是黄牌，不可再发黄牌!');
            }
        }
        $is_card=$project->is_card;
        if($is_card == 1)
        {
            if($color == 1)
            {
                return $this->failed('此项目已经是黄牌，不可再发黄牌!');
            }
        }elseif($is_card == 2)
        {
            return $this->failed('此项目已经是红牌，不可再发牌!');
        }
        //添加记录
        $data['pid'] = $pid;
        $data['color'] = $color;
        $data['remark'] = $remark;
        $data['fuid'] = $uid;
        $data['addtime'] = time();
        $record = Card::insert($data);
        if ($record) {
            //更新主表字段
            $project->is_card=$color;
            $project->save();
            return $this->success('发牌成功');
        } else {
            return $this->failed('发牌失败!');
        }
    }

    /*
     * 撤销发牌
     * */
    public function carddel(Request $request)
    {
        $pid=$request->get('pid'); //项目id
        $remark=$request->get('remark'); //发牌备注
        $uid=$request->get('uid'); //发牌人id
        $project = Project::find($pid);
        if (empty($remark)) {
            return $this->failed('备注不能为空!');
        }
        if(empty($project))
        {
            return $this->failed('项目不存在！');
        }
        if($project->is_card == 0)
        {
            return $this->failed('该项目未人工发牌！');
        }
        //添加记录
        $data['pid'] = $pid;
        $data['color'] = 3;
        $data['remark'] = $remark;
        $data['fuid'] = $uid;
        $data['addtime'] = time();
        $record = Card::insert($data);
        if ($record) {
            //更新主表字段
            $project->is_card=0;
            $project->save();
            return $this->success('撤销发牌成功');
        } else {
            return $this->failed('撤销发牌失败!');
        }

    }

    //发牌记录
    public function cardlist(Request $request)
    {

        $pid=$request->get('pid'); //项目id
        $project=Project::where(['id'=>$pid])->first(['id','pname'])->toArray();
        if(empty($project))
        {
            return $this->failed('项目不存在！');
        }
        $record=Card::leftJoin('project_plan_custom','project_plan_custom.id','=','card.custom_id')
            ->leftJoin('project_progress','project_progress.id','=','card.progress_id')
            ->where(['card.pid'=>$pid])
            ->orderBy('card.pid','desc', 'card.color','asc', 'project_progress.month','asc')
            ->select('card.y_time','card.pid','card.fuid','card.color','card.addtime','card.custom_id','card.remark','card.id','project_plan_custom.m_value','project_progress.month')
            ->get()
            ->toArray();
        $custom_info = array();
        $custom_info_new = array();
        foreach ($record as $key => $value) {
            if ($value['color'] == 1) {
                $record[$key]['status'] = '进展缓慢';
                $record[$key]['status_ys'] = 'pro3';
            } elseif($value['color'] == 2) {
                $record[$key]['status'] = '严重滞后';
                $record[$key]['status_ys'] = 'pro4';
            }elseif($value['color'] == 3)
            {
                $record[$key]['status'] = '撤销发牌';
                $record[$key]['status_ys'] = 'pro1';
            }
            if ($value['fuid'] == 0) {
                $record[$key]['fpr'] = '系统发牌';
            } else {
                $user = User::getUserName($value['fuid']);
                if ($user) {
                    $record[$key]['fpr'] = $user;
                } else {
                    $record[$key]['fpr'] = '五化办';
                }
            }
            $day = str_replace('-', '', $value['y_time']);
            $record[$key]['day'] = $day;
        }

        //每个项目节点 只显示一次
        foreach ($record as $k => $v) {
            if($v['custom_id'] !='0'){
                $custom_info[$v['custom_id']][] = $v;
            }else{
                $custom_info[$v['id']][] = $v; //过滤人工发牌
            }
        }
        foreach ($custom_info as $k_cus => $item) {
            $min_list = min($item); //取最大逾期天数
            $custom_info_new[] = $min_list; //过滤数组 每个节点只取一条记录
        }
        //统计
        $count_hm=0;
        $count_zh=0;
        foreach ($custom_info_new as $key => $value) {
            $custom_info_new[$key]['addtime']=date('Y-m-d h:i:s', $value['addtime']);
            if($value['color'] == 1)
            {
                $count_hm++;
            }elseif ($value['color'] == 2){
                $count_zh++;
            }
        }
        $data['pname']=$project['pname'];
        $data['hm']=$count_hm;
        $data['zh']=$count_zh;
        $data['record']=$custom_info_new;
        return $this->success($data);
    }

    //单个项目得分详情
    public function score(ProjectRequest $projectRequest,ScoreService $scoreService){
        $score_id = $projectRequest->input('score_id');//month_score 自增ID
        $pid = $projectRequest->input('id') ?? '';//项目ID
        try{
            if(!$score_id){
                $html = $scoreService->computeScore($pid);
            }else{
                $html = MonthScoreHistory::where(['score_id'=>$score_id,'pid'=>$pid])->value('data') ?? '暂无数据!';
            }
            return $this->success($html);
        }catch (\Exception $e){
            return $this->failed('获取失败！');
        }
        //return $html;
    }

    //生成pdf
    public function exportpdf(Request $request) {

        $url = $request->input('url');
        if (empty($url)) {
            return $this->failed('url不能为空',400);
        }

        $url = 'http://192.168.0.37/#/login';
        $exe_file = config('snappy.pdf')['binary'];

        $filename = md5(uniqid());
        $path_file = public_path().'/pdf/'.$filename.'.pdf';

        fopen($path_file, "w+");
        chmod($path_file, 0777);

        //生成PDF文件
        $res = shell_exec("{$exe_file} {$url} {$path_file}");

        $topdf = $path_file;
        if (file_exists($topdf)) {
//            header("Content-type:application/pdf");
//            header('Access-Control-Allow-Origin: *','*');
//            header('Access-Control-Allow-Headers:Origin,Content-Type,Cookie,Accept,multipart/form-data,application/json,X-Auth-Token');
//            header('Access-Control-Allow-Methods:GET,POST,PATCH,PUT,OPTION');
//            header('Pragma: public');
//            header('Expires: 0');
//            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
//            header('Content-Transfer-Encoding: binary');
//            header('Content-Length: ' . filesize($topdf));
//            //pdf预览模式下可下载
//            header('Content-Disposition: inline; filename='.$topdf);
//            //直接在浏览器中下载
//            //header("Content-Disposition:attachment;filename='$filename.pdf'");
//            // send document to the browser
//            echo file_get_contents($topdf);
            $fileUrl = URL::to('/').'/pdf/'.$filename.'.pdf';
            return $fileUrl;
        } else {
           return $this->failed('文件不存在',400);
        }
    }


    // 续建关联往年项目,
    // 点击搜索，出现相关项目的接口
    public function selectProject(Request $request){
        $keywords = $request->keywords ?? '';
        if($keywords){
            $res = Project::where('pname', 'like', "%{$keywords}%")->get(['id','pname'])->toArray();
            return $this->success($res);
        }else{
            return $this->failed('搜索内容不能为空',400);
        }
        
    }
}
