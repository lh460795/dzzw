<?php
/**
 * Created by PhpStorm.
 * User: Watanabe Dai
 * Date: 2019/5/29
 * Time: 16:14
 */
namespace App\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\Controller;
use App\Models\UserLive;
use Illuminate\Http\Request;
use App\Models\Corp;
use App\Models\User;
use App\Models\LoginLog;
use App\Models\ActivityLog;
use App\Models\Unit;
use App\Models\ActivityType;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Writers\LaravelExcelWriter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class UserController extends Controller{

    public function UserCenter(){

        try{

            $user = \Auth::guard('api')->user();

            $permissions = $user->getAllPermissions()->toArray();

            $tree=$this->trees($type=2,$permissions);

            $permission_tree=empty($tree)?:$this->compose($tree);

            $func=function($permission_tree) use (&$func){
                foreach($permission_tree as $key => $value){
                    isset($value['navName'])&&$data[$key]['navName']=$value['navName'];
                    isset($value['navRoute'])&&$data[$key]['navRoute']=$value['navRoute'];
                    isset($value['itemName'])&&$data[$key]['itemName']=$value['itemName'];
                    isset($value['icon_id'])&&$data[$key]['itemIcon']=$value['icon_id'];
                    isset($value['route'])&&$data[$key]['route']=$value['route'];
                    if(isset($value['children'])){
                        array_multisort(array_column($value['children'],'sort'),SORT_ASC,$value['children']);
                        $data[$key]['children']=$func($value['children']);
                    }
                }
                return $data;
            };

            return $this->respond($func($permission_tree));

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }

    }

    public function AddressBook(Request $request)
    {
        try {

                $users = User::filter($request->all())->get();

                foreach($users as $key=>$user){
                    $data[$key]['level']=$user->corp->level;
                    $data[$key]['username']=$user->username;
                    $data[$key]['corp_name']=$user->corp->name;
                    $data[$key]['phone']=$user->phone;
                }
                array_multisort(array_column($data,'level'),SORT_ASC,$data);

                return $this->respond($data);

        } catch (\Exception $e) {

            return $this->failed($e->getMessage(), 500);


        }

    }

    public function getUnits(Request $request){

        try{

            $units = Unit::filter($request->all())->get();

            foreach($units as $key =>$unit){
                $data[$key]['id']=$unit->id;
                $data[$key]['name']=$unit->name;
                $data[$key]['alias_name']=$unit->alias_name;
                $data[$key]['parent_id']=$unit->parent_id;
                if($unit->corp->toArray()){
                    foreach($unit->corp as $item=>$corp){
                        $data[$key]['corp'][$item]['corp_id']=$corp->id;
                        $data[$key]['corp'][$item]['name']=$corp->name;
                        $data[$key]['corp'][$item]['alias_name']=$corp->alias_name;
                        $data[$key]['corp'][$item]['units_id']=$corp->units_id;
                    }
                }
            }

            return $this->respond($data);

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }

    }

    public function UserLiveNum(){

        try{

            $userLive_num=UserLive::filter(['is_live'=>1])->count();

            return $userLive_num>0?$this->respond(['online_num'=>$userLive_num]):$this->message('系统错误',500);

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }

    }


    public function loginDataStatistics(Request $request){

        try{

            $loginLogs = LoginLog::filter($request->all())->get()->groupBy([function($item){

                return date('Y-m-d',$item['login_time']);

            },'user_id'])->reverse();

            $data=[];

            foreach($loginLogs as $date =>$loginLog){
                $data[$date]['date']=$date;
                foreach($loginLog as $user_id =>$loginLogInfo){
                   $data[$date]['record'][$user_id]['unit']=User::find($user_id)->units;
                   $data[$date]['record'][$user_id]['user_name']=User::find($user_id)->username;
                   $data[$date]['record'][$user_id]['num']=count($loginLogInfo);
                }
                $data[$date]['record']=array_values($data[$date]['record']);
            }

            return $this->respond(paginateCollection(Collect(array_values($data))));

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }

    }

    public function exportLoginDataStatistics(Request $request){

        try{

            $loginLogs = LoginLog::filter($request->all())->get()->groupBy([function($item){

                return date('Y-m-d',$item['login_time']);

            },'user_id'])->reverse();


            if(!empty($loginLogs->toArray())){
                $rows=[
                    ['登录日期','单位','人员','登录次数'],
                ];
                foreach($loginLogs as $date =>$loginLog){
                    $rows[$date]['date']=$date;
                    foreach($loginLog as $user_id =>$loginLogInfo){
                        $rows[$date]['record'][$user_id]['unit']=User::find($user_id)->units;
                        $rows[$date]['record'][$user_id]['user_name']=User::find($user_id)->username;
                        $rows[$date]['record'][$user_id]['num']=count($loginLogInfo);
                    }
                    $rows[$date]['record']=array_values($rows[$date]['record']);
                }
                $rows=array_values($rows);

                $excelFileName='LoginDataStatistics'.date('YmdHis');

                $filePath=public_path('office/');

                Excel::create($excelFileName,function(laravelExcelWriter $excel)use($excelFileName,$rows){
                    $excel->setTitle($excelFileName);
                    $excel->sheet('登录历史记录统计',function(\PHPExcel_Worksheet $sheet)use($rows){
                        $cum=1;
                        foreach($rows as $key => $row){
                            if($key>0){
                                $num=count($row['record']);
                                $cum+=$num;
//                                if($key==1){
//                                    $sheet->cell('A'.($key+1),$row['date']);
//                                    $num>1&&$sheet->mergeCells("A".($key+1).":A".($key+1+$num));
//                                }else{
                                    $sheet->cell('A'.$cum,$row['date']);
                                    $num>1&&$sheet->mergeCells("A".($cum-$num+1).":A".$cum);
//                                }
                                foreach($row['record'] as $item =>$value){
                                    $sheet->cell('B'.($cum-$num+$item+1),$value['unit']);
                                    $sheet->cell('C'.($cum-$num+$item+1),$value['user_name']);
                                    $sheet->cell('D'.($cum-$num+$item+1),$value['num']);
                                }
                            }else{
                                $sheet->fromArray($row);
                            }
                        }
                    });
                })->store('xls',$filePath);

                $fileUrl=URL::to('/').'/office/'.$excelFileName.'.xls';

                return $fileUrl;

            }else{
                return $this->failed('没有登录数据',404);
            }

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }

    }


    public function LoginRecord(Request $request){

        try{

                $LoginLogs=LoginLog::with('user_lives')
                    ->select(['login_time','user_name','login_ip','platform','units','user_id'])
                    ->filter($request->all())
                    ->orderby('login_time','desc')
                    ->Paginate($request->per_page?$request->per_page:15);

                foreach($LoginLogs as $key=>$LoginLog){

                    !empty($LoginLog->login_time)&&$LoginLogs[$key]->login_date=date('Y-m-d H:i:s',$LoginLog->login_time);

                    unset($LoginLog->login_time);

                    !empty($LoginLog->user_lives)?$LoginLogs[$key]->user_status='在线':$LoginLogs[$key]->user_status='下线';

                    unset($LoginLog->user_lives);

                }

                return $this->respond($LoginLogs);

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }

    }

    public function exportLoginRecord(Request $request){

        try{
            $LoginLogs=LoginLog::with('user_lives')->select(['login_time','user_name','login_ip','platform','units','user_id'])->filter($request->all())->orderby('login_time','desc')->get();

            $rows=[
                ['登录时间','登录平台','登录人','在线状态','单位','ip'],
            ];

            foreach($LoginLogs as $item){
                !empty($item->user_lives)?$item->user_status='在线':$item->user_status='下线';
                $rows[]=[
                    $item->login_date=date('Y-m-d H:i:s',$item->login_time),
                    $item->platform,
                    $item->user_name,
                    $item->user_status,
                    $item->units,
                    $item->login_ip
                ];
            }
            $excelFileName='LoginRecord'.date('YmdHis');

            $filePath=public_path('office/');
            Excel::create($excelFileName,function(laravelExcelWriter $excel)use($excelFileName,$rows){
                $excel->setTitle($excelFileName);
                $excel->sheet('登录历史记录',function(\PHPExcel_Worksheet $sheet)use($rows){
                    $sheet->fromArray($rows);
                });
            })->store('xls',$filePath);

            $fileUrl=URL::to('/').'/office/'.$excelFileName.'.xls';

            return $fileUrl;

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }

    }

    public function onlineUsers(Request $request){

        try{

            $onlineUsers=UserLive::with(['area','unit'])->select(['username','phone','district_id','units_id','platform','ip','updatetime'])->filter($request->all())->Paginate(15);

            foreach($onlineUsers as $key=>$onlineUser){
                $onlineUsers[$key]->updated_date=date('Y-m-d H:i:s',$onlineUser->updatetime);
                $onlineUsers[$key]->area_name=$onlineUser->area->aname;
                $onlineUsers[$key]->unit_name=$onlineUser->unit->name;
                $onlineUsers[$key]->unit_fullname=$onlineUser->area->fullname;
                unset($onlineUser->area);
                unset($onlineUser->unit);
                unset($onlineUser->updatetime);
            }

            return $this->respond($onlineUsers);

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }

    }

    public function exportActivityLog(Request $request){

        try{

                $ActivityLogs=ActivityLog::select(['created_at','type','description','causer_id','ip'])->filter($request->all())->orderby('created_at','desc')->get();

                $rows=[
                    ['操作时间','操作类型','操作描述','操作人','ip'],
                ];

                foreach ($ActivityLogs as $item){
                    $rows[]=[
                        $item->created_at,
                        $item->type,
                        $item->description,
                        $item->operator=$item->user->username,
                        $item->ip
                    ];
                }

                $excelFileName='ActivityLog'.date('YmdHis');

                $filePath=public_path('office/');

                Excel::create($excelFileName,function(laravelExcelWriter $excel)use($excelFileName,$rows){
                    $excel->setTitle($excelFileName);
                    $excel->sheet('操作列表',function(\PHPExcel_Worksheet $sheet)use($rows){
                        $sheet->setStyle(array(
                            'font' => array(
                                'name'      =>  'Microsoft YaHei',
                                'size'      =>  13,
                                'bold'      =>  true
                            )
                        ));
                        $sheet->fromArray($rows);
                    });
                })->store('xls',$filePath);

                $fileUrl=URL::to('/').'/office/'.$excelFileName.'.xls';

                return $fileUrl;

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }

    }

    public function ActivityRecord(Request $request){

        try{

                $activityLogs=ActivityLog::select(['created_at','type','description','causer_id','ip'])->filter($request->all())->orderby('created_at','desc')->Paginate($request->per_page);

                $data=$activityLogs->toArray();

                foreach($activityLogs as $key=>$activityLog){

                    $data['data'][$key]['operator']=$activityLog->user->username;
                }

                $data['start_date']=$request->start_date;

                $data['end_date']=$request->end_date;

                return $this->respond($data);

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }

    }

    public function getActivityType(){

        try{

            $data=ActivityType::get(['id','activity_type'])->toArray();

            return !empty($data)?$this->respond($data):$this->failed('获取操作类型有误',404);

        }catch(\Exception $e){

            return $this->failed($e->getMessage(),500);

        }

    }
}