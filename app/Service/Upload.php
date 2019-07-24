<?php

namespace App\Service;
use Illuminate\Http\Request;


abstract class Upload
{
    protected $fileField; //文件域名
    protected $file; //文件上传对象
    protected $base64; //文件上传对象
    protected $config; //配置信息
    protected $oriName; //原始文件名
    protected $fileName; //新文件名
    protected $fullName; //完整文件名,即从当前配置目录开始的URL
    protected $filePath; //完整文件名,即从当前配置目录开始的URL
    protected $fileSize; //文件大小
    protected $fileType; //文件类型
    protected $stateInfo; //上传状态信息,
    protected $stateMap; //上传状态映射表，国际化用户需考虑此处数据的国际化
    protected $fileData; //文件的临时保存数组
    abstract function doUpload(); //抽象方法,上传核心方法

    public function __construct(array $config, Request $request)
    {
        $this->config = $config;
        $this->request = $request;
        $this->fileField = $config['fieldName'];
        $this->district =  $config['district'];
        if(isset($config['allowFiles'])){
            $this->allowFiles=$config['allowFiles'];
        }else{
            $this->allowFiles=[];
        }

        $stateMap = [
            "SUCCESS",
            "文件大小超出 MAX_FILE_SIZE 限制",
            "文件未被完整上传",
            "没有文件被上传",
            "上传文件为空",
            "ERROR_TMP_FILE" => "临时文件错误",
            "ERROR_TMP_FILE_NOT_FOUND" => "找不到临时文件",
            "ERROR_SIZE_EXCEED" =>"文件大小超出网站限制",
            "ERROR_TYPE_NOT_ALLOWED" => "文件类型不允许",
            "ERROR_CREATE_DIR" => "目录创建失败",
            "ERROR_DIR_NOT_WRITEABLE" => "目录没有写权限",
            "ERROR_FILE_MOVE" => "文件保存时出错",
            "ERROR_FILE_NOT_FOUND" => "找不到上传文件",
            "ERROR_WRITE_CONTENT" => "写入文件内容错误",
            "ERROR_UNKNOWN" =>  "未知错误",
            "ERROR_DEAD_LINK" => "链接不可用",
            "ERROR_HTTP_LINK" =>  "链接不是http链接",
            "ERROR_HTTP_CONTENTTYPE" =>  "链接contentType不正确",
            "ERROR_UNKNOWN_MODE" =>  "文件上传模式错误",
        ];

        $this->stateMap=$stateMap;

    }

    /**
     *
     *
     *
     * @return array
     */

    public function upload()
    {
        $this->doUpload();

        return $this->getFileInfo();
    }


    /**
     * 上传错误检查
     * @param $errCode
     * @return string
     */
    protected function getStateInfo($errCode)
    {
        return !$this->stateMap[$errCode] ? $this->stateMap["ERROR_UNKNOWN"] : $this->stateMap[$errCode];
    }

    /**
     * 文件大小检测
     * @return bool
     */
    protected function  checkSize()
    {
        return $this->fileSize <= ($this->config["maxSize"]);
    }

    /**
     * 获取文件扩展名
     * @return string
     */
    protected function getFileExt()
    {
        return '.' . $this->file->getClientOriginalExtension();
    }

    /**
     * 重命名文件
     * @return string
     */
    protected function getFullName()
    {
        //替换日期事件
        $t = time();
        $d = explode('-', date("Y-y-m-d-H-i-s"));
        $format = $this->district. $this->config["pathFormat"];
        $format = str_replace("{yyyy}", $d[0], $format);
        $format = str_replace("{yy}", $d[1], $format);
        $format = str_replace("{mm}", $d[2], $format);
        $format = str_replace("{dd}", $d[3], $format);
        $format = str_replace("{hh}", $d[4], $format);
        $format = str_replace("{ii}", $d[5], $format);
        $format = str_replace("{ss}", $d[6], $format);
        $format = str_replace("{time}", $t, $format);

        //过滤文件名的非法自负,并替换文件名
        $oriName = substr($this->oriName, 0, strrpos($this->oriName, '.'));
        $oriName = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $oriName);
        $format = str_replace("{filename}", $oriName, $format);

        //替换随机字符串  数值太大可能导致部分环境报错
        $randNum = rand(1, 1000000000) . rand(1, 1000000000);
        if (preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)) {
            $format = preg_replace("/\{rand\:[\d]*\}/i", substr($randNum, 0, $matches[1]), $format);
        }

        $ext = $this->getFileExt();
        return $format . $ext;
    }

    /**
     * 获取文件完整路径
     * @return string
     */
    protected function getFilePath()
    {
        $fullName = $this->fullName;

        $rootPath = public_path();

        $fullName = ltrim($fullName, '/');


        return $rootPath . '/' . $fullName;
    }
    /**
     * 文件类型检测
     * @return bool
     */
    protected function checkType()
    {

        return in_array($this->getFileExt(), $this->config["allowFiles"]);
    }
    /**
     * 获取当前上传成功文件的各项信息
     * @return array
     */
    public function getFileInfo()
    {
        //暂时屏蔽 多文件上传   2019.06.25
//        return $this->fileData;
        return array(
            "state" => $this->stateInfo,
            "url" => $this->fullName,
            "title" => $this->fileName,
            "original" => $this->oriName,
            "type" => trim($this->fileType,'.'),
            "size" => $this->fileSize
        );
    }

}
