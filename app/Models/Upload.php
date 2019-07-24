<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;


//后台上传model类
class Upload extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at']; // 软删除
//    protected $table = 'uploads';
    protected $fillable = [
        'pid','cid','uid','url', 'filename', 'file_new_name',
        'ext','type','file_type','size','month','m_progress_id',
        'relation_id','add_time'
    ];

    /**
     * Notes: 附件类型
     * User: lh
     * Date: 2019-05-07
     * Time: 15:33
     * @return array
     */
    public static function file_type(){
        return ['后台附件','申报附件','进度附件','调整附件',
            '完结附件','未完成附件','点评附件','督办函附件','督办回复附件'];
    }

    /**
     * Notes: 附件类型 列表显示
     * User: lh
     * Date: 2019-05-06
     * Time: 19:32
     * @param $file_type
     * @return string
     */
    public static function formatByfile_type($file_type) {
        switch ($file_type)
        {
            case 0:
                return '后台附件';
                break;
            case 1:
                return '申报附件';
                break;
            case 2:
                return '进度附件';
                break;
            case 3:
                return '调整附件';
                break;
            case 4:
                return '完结附件';
                break;
            case 5:
                return '未完成附件';
                break;
            case 6:
                return '点评附件';
                break;
            case 7:
                return '督办函附件';
                break;
            case 8:
                return '督办回复附件';
                break;
            default:
                return '后台附件';
        }
    }


    //通过上传提交的数字来判断是哪个模块的上传操作
    public static function formatBypath($uppath) {
        switch ($uppath)
        {
            case 0:
                return 'Admin';
                break;
            case 1:
                return 'Shenbao';
                break;
            case 2:
                return 'Progress';
                break;
            case 3:
                return 'Tiaozheng';
                break;
            case 4:
                return 'Complete';
                break;
            case 5:
                return 'Uncomplete';
                break;
            case 6:
                return 'AdminPinglun';
                break;
            case 7:
                return 'Duban';
                break;
            default:
                return 'Admin';
        }
    }

    //附件上传入库操作
   public static function upload($pid,$relation_id,$uid,$file_type,$fileList,$type=0)
    {
        $res='';
        foreach ($fileList as $value)
        {
            $upload['pid']=$pid;   //关联项目主表id
            $upload['relation_id']=$relation_id;  //关联表id
            $upload['uid']=$uid;    //附件上传人
            $upload['url']=$value->url;
            $upload['filename']=$value->original;  //原名
            $upload['file_new_name']=$value->title;  //文件名(重命名)
            $upload['ext']=$value->type;  //文件格式
            $upload['type']=$type;  //1.基础材料，2分项材料
            $upload['file_type']=$file_type;  //附件类型 0:后台附件 1：申报 2：填报进度 3：调整 4：完结 5：未完成 6：点评 7：督办函 8：督办回复
            $upload['add_time']=time();
            $res=Upload::insert($upload);
        }
        return $res;
    }


}
