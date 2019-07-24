<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Requests\Api\ProjectRequest;
use App\Http\Resources\Api\V1\Frontend\ProjectCollection;
use App\Http\Resources\Api\V1\Frontend\ProjectDraftCollection;
use App\Http\Resources\Api\V1\Frontend\ProjectResource;
use App\Models\Project;
use App\Models\ProjectDraft;
use App\Models\ProjectPlanCustom;
use App\Models\ProjectPlanCustomSave;
use App\Models\ProjectPlanTemplate;
use App\Models\Unit;
use App\Work\Model\FlowProcess;
use App\Work\Model\Run;
use App\Work\Model\RunProcess;
use App\Work\Repositories\ProcessRepo;
use App\Work\Workflow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use Illuminate\Support\Facades\DB;


class DraftController extends Controller
{



    //获取立项项目详情
    public function show(Request $request){
        $user = Auth::guard('api')->user();
        $units_id = $user->units_id ?? 1;//测试接口用
        $units_alias_name = $user->units ?? '孝感市发展和改革委员会';//测试接口用
        $units_name = $user->units ??'市发改委';
        $pid = $request->input('pid');
        $project_info = ProjectResource::make(
            Project::with(['plancustom' => function ($query) use ($pid) {
                //->hide(['form.id'])
            },'runlog'])->find($pid)
        );

        if (!empty($project_info['plancustom'])) {
            $plan =$project_info['plancustom']->toArray();//项目节点
        } else {
            $plan = [];
        }

        $runlog =$project_info['runlog'];
        $array =[];
        $response= [];
        if(!empty($plan)){
            foreach ($plan as $k => $val) {
                $array[$val['p_name']][] = $val;
            }
            $array = array_values($array);

            //拼接成前端要的格式
            $m_zrdw = [];
            foreach ($array as $k => $item) {
                foreach ($item as $k2 => $item2) {
                    //dump($item2);
                    $response[$k][$k2]['_jc_p']['value'] = $item2['p_value']; //1级节点
                    $response[$k][$k2]['_jc_c']['value'] = $item2['m_value']; //2级节点
                    $response[$k][$k2]['_jc_p']['type'] = 'input';
                    $response[$k][$k2]['_jc_c']['type'] = 'input';

                    for ($i = 1; $i <= 12; $i++) { //月份
                        if(($item2['content'.$i] !='') || ($item2['content'.$i] !=null)){
                            $response[$k][$k2]['month' . $i]['value'] = true;
                        }
                        else{
                            $response[$k][$k2]['month' . $i]['value'] = '';
                        }
                        $response[$k][$k2]['month' . $i]['type'] = 'checkbox';
                        $response[$k][$k2]['month' . $i]['content'] = $item2['content'.$i];
                    }
                    $m_zrdw=explode(',',$item2['m_zrdw']);
                    //责任单位
                    $response[$k][$k2]['select']['value'] = $m_zrdw;
                    $response[$k][$k2]['select']['name'] = Unit::getNames($item2['m_zrdw']);
                    $response[$k][$k2]['select']['type'] ='select';
                }
            }
            $project_info['plancustom'] = $response;
        }
        //dd($project_info);
        return $this->success($project_info);
    }

    //项目列表
    public function index(Request $request) {
        $per_page = empty($request->input('per_page'))?15:$request->input('per_page');
        $data = ProjectDraft::where('uid', Auth::guard('api')->id())
            ->filter($request->all())
            ->orderBy('created_at', 'desc')
            ->paginate($per_page);
        $data = new ProjectDraftCollection($data);
        return $this->success($data);
    }




    //保存草稿功能
    public function create(Request $request){
        $user = Auth::guard('api')->user();
//        $uid = Auth::guard('api')->user()->id ??1;
        $uid = $request->get('uid') ??1;//测试接口用
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
        $project_form = $data_array['form'];//获取表单基础数据
        $project_form['year'] = date('Y', time()); //当前年份
        $project_form['uid'] = $uid;
        $project_form['units_id'] = $uid;
        $project_form['units_dis'] = $uid;
        $project_form['units_type'] = $uid;
        $project_form['units_area'] = $uid;
        $project_form['units_area'] = $uid;

        if(!empty($project_form['xieban'])){
            //拼接数据 协办 按照旧数据格式
            $xieban ='';
            $xie_fuzhe = '';
            foreach ($project_form['xieban'] as $k=>$item){
                $xieban .=$item['cooprateCorp'].'|';
                $xie_fuzhe .=$item['cooprateMan'].'|';
            }
            $project_form['xieban'] = rtrim($xieban,'|');
            $project_form['xie_fuzhe'] = rtrim($xie_fuzhe,'|');
        }

        //手动创建验证 request类不支持json 参数
        //\Validator::make($project_form, $rules,$message)->validate();

        DB::beginTransaction();
        try {
            $project = ProjectDraft::create($project_form);
            $plan_info = $data_array['plan'];//获取计划节点数据
            $plan_form = [];
            $plan_form_create = [];

            foreach ($plan_info as $k => $item_plan) {
                foreach ($item_plan as $p => $item) {
                    $plan_form[$k][$p]['pid'] = $project->id;
                    $plan_form[$k][$p]['p_name'] = $k . '_jc_p';
                    $plan_form[$k][$p]['p_value'] = $item['_jc_p']['value']; //1级节点
                    $plan_form[$k][$p]['m_name'] = $k . '_jc_c';
                    $plan_form[$k][$p]['m_value'] = $item['_jc_c']['value']; //2级节点
                    $plan_form[$k][$p]['m_zrdw']= implode(',',$item['select']['value']); //组成字符串
                    $p_month_str = '';//拼接月份字符串
                    for ($i = 1; $i <= 12; $i++) { //月份
                        if ($item['month' . $i]['value'] == true) {
                            $plan_form[$k][$p]['content' . $i] = $item['month' . $i]['content']; //获取节点内容
                            $p_month_str .= $i . ',';//拼接月份
                        }else{
                            $plan_form[$k][$p]['content' . $i] ='';
                        }
                    }
                    $plan_form[$k][$p]['p_year'] = $project->year;
                    $plan_form[$k][$p]['p_month'] = rtrim($p_month_str, ',') ?? '';
                    $plan_form[$k][$p]['uid'] = Auth::guard('api')->id();
                    $plan_form_create[] = $plan_form[$k][$p];
                }
            }


            $project_plan = ProjectPlanCustomSave::insert($plan_form_create); //插入节点表

            DB::commit();
            return $this->message('保存成功');
        } catch (\Exception $e) {
            DB::rollback();
            return $e->getMessage();
            return $this->failed('保存失败');
        }
    }


    //删除草稿
    public function delete(Request $request){
        $id = $request->input('id');
        if (empty($id)) {
            return $this->message('参数错误', 400);
        }

        $id = json_decode($id, true);

        DB::beginTransaction();

        try {

            //删除项目表草稿和节点表草稿
            ProjectDraft::where('uid', Auth::guard('api')->id())
                        ->whereIn('id', $id)
                        ->delete();

            ProjectPlanCustomSave::whereIn('pid', $id)
                                  ->where('uid', Auth::guard('api')->id())
                                  ->delete();
            DB::commit();
            return $this->message('删除成功');
        } catch (\Exception $e) {
            DB::rollback();
            return $e->getMessage();
            return $this->failed('删除失败');
        }

    }

    //一键清空草稿
    public function truncate() {
        DB::beginTransaction();

        try {
            //删除项目表草稿和节点表草稿
            ProjectDraft::where('uid', Auth::guard('api')->id())
                ->delete();
            ProjectPlanCustomSave::where('uid', Auth::guard('api')->id())->delete();
            DB::commit();
            return $this->message('清空成功');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->failed('清空失败');
        }
    }
}
