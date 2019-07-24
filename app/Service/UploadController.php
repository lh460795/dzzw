<?php
namespace App\Service;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Traits\Api\ApiResponse;
class Controller extends BaseController
{

    use ApiResponse;


    public function __construct()
    {

    }


    public function upload(Request $request)
    {
        $config = config('Upload.upload');

        $action = $request->get('action');


        switch ($action) {
            case 'uploadimage':
                $upConfig = array(
                    "pathFormat" => $config['imagePathFormat'],
                    "maxSize" => $config['imageMaxSize'],
                    "allowFiles" => $config['imageAllowFiles'],
                );
                $result = with(new UploadFile($upConfig, $request))->upload();
                break;
            case 'uploadvideo':
                $upConfig = array(
                    "pathFormat" => $config['videoPathFormat'],
                    "maxSize" => $config['videoMaxSize'],
                    "allowFiles" => $config['videoAllowFiles'],
                );
                $result = with(new UploadFile($upConfig, $request))->upload();

                break;
            case 'uploadfile':
            default:
                $upConfig = array(
                    "pathFormat" => $config['filePathFormat'],
                    "maxSize" => $config['fileMaxSize'],
                    "allowFiles" => $config['fileAllowFiles'],
                );
                $result = with(new UploadFile($upConfig, $request))->upload();
                break;
        }

        return $this->success($result);

    }


}
