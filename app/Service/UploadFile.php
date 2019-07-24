<?php
namespace App\Service;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class UploadFile  extends Upload {

    public function doUpload()
    {
        $file = $this->request->file($this->fileField);

            if (empty($file)) {
                $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
                return false;
            }
            if (!$file->isValid()) {
                $this->stateInfo = $this->getStateInfo($file->getError());
                return false;
            }
            $this->file = $file;
            $this->oriName = $this->file->getClientOriginalName();  //文件名
            $this->fileSize = $this->file->getSize();
            $this->fileType = $this->getFileExt();
            $this->fullName = $this->getFullName();
            $this->filePath = $this->getFilePath();
            $this->fileName = basename($this->filePath);
            //检查文件大小是否超出限制
            if (!$this->checkSize()) {
                $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
                return false;
            }
            //检查是否不允许的文件格式
            if (!$this->checkType()) {
                $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
                return false;
            }
            try {
                $this->file->move(dirname($this->filePath), $this->fileName);
                $this->stateInfo = $this->stateMap[0];
            } catch (FileException $exception) {
                $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
                return false;
            }


            //暂时屏蔽 多文件上传   2019.06.25
//        foreach ($file as $key=>$val)
//        {
//            if (empty($val)) {
//                $this->stateInfo = $this->getStateInfo("ERROR_FILE_NOT_FOUND");
//                $this->fileData[$key]['state']=$this->stateInfo;
//                return false;
//            }
//            if (!$val->isValid()) {
//                $this->stateInfo = $this->getStateInfo($val->getError());
//                $this->fileData[$key]['state']=$this->stateInfo;
//                return false;
//            }
//
//            $this->file = $val;
//
//            $this->oriName = $this->file->getClientOriginalName();  //文件名
//            $this->fileData[$key]['original']=$this->oriName;
//            $this->fileSize = $this->file->getSize();
//            $this->fileData[$key]['size']=$this->fileSize;
//            $this->fileType = $this->getFileExt();
//            $this->fileData[$key]['type']=$this->fileType;
//            $this->fullName = $this->getFullName();
//            $this->fileData[$key]['url']=$this->fullName;
//
//            $this->filePath = $this->getFilePath();
//
//            $this->fileName = basename($this->filePath);
//            $this->fileData[$key]['title']=$this->fileName;
//
//            //检查文件大小是否超出限制
//            if (!$this->checkSize()) {
//                $this->stateInfo = $this->getStateInfo("ERROR_SIZE_EXCEED");
//                $this->fileData[$key]['state']=$this->stateInfo;
//                return false;
//            }
//            //检查是否不允许的文件格式
//            if (!$this->checkType()) {
//                $this->stateInfo = $this->getStateInfo("ERROR_TYPE_NOT_ALLOWED");
//                $this->fileData[$key]['state']=$this->stateInfo;
//                return false;
//            }
//
//
//            try {
//                $this->file->move(dirname($this->filePath), $this->fileName);
//
//                $this->stateInfo = $this->stateMap[0];
//                $this->fileData[$key]['state']=$this->stateInfo;
//            } catch (FileException $exception) {
//                $this->stateInfo = $this->getStateInfo("ERROR_WRITE_CONTENT");
//                $this->fileData[$key]['state']=$this->stateInfo;
//                return false;
//            }
//        }
        return true;

    }
}
