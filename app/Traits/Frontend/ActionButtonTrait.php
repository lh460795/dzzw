<?php

namespace App\Traits\Frontend;

trait ActionButtonTrait
{

    //新增按钮
    public function createButton($actionModel) {
        if (auth()->guard('api')->user()->can("'api'.{$actionModel}.create")){
            return true;
        }
        return false;
    }

    //编辑按钮
    public function editButton($actionModel)
    {

        if (auth()->guard('api')->user()->can("'api'.{$actionModel}.edit")){
            return true;
        }
        return false;
    }

    //删除按钮
    public function deleteButton($actionModel)
    {
        if (auth()->guard('api')->user()->can("'api'.{$actionModel}.destroy")){
            return true;
        }
        return false;
    }

    public function truncateButton($actionModel)
    {
        if (auth()->guard('api')->user()->can("'api'.{$actionModel}.truncate")){
            return true;
        }

        return false;
    }

    public function banButton($actionModel)
    {
        if (!empty($id)){
            $this->id = $id;
        }
        if (auth()->user()->can("{$actionModel}.ban")){
            return true;
        }
        return false;
    }






    //通过
    public function passButton($actionModel) {
        if (auth()->guard('api')->user()->can("'api'.{$actionModel}.pass")){
            return true;
        }
        return false;

    }

    //驳回
    public function rejectButton($actionModel) {
        if (auth()->guard('api')->user()->can("'api'.{$actionModel}.reject")){
            return true;
        }

        return false;
    }


    //查看
    public function showButton($actionModel) {
        if (auth()->guard('api')->user()->can("'api'.{$actionModel}.show")){
            return true;
        }
        return false;
    }

    public function importButton($actionModel) {
        if (auth()->user()->can("'api'.{$actionModel}.importResource")){
            return true;
        }
        return false;
    }



    public function exportButton($actionModel) {
        if (auth()->guard('api')->user()->can("'api'.{$actionModel}.export")){
            return true;
        }
        return false;
    }

    public function superviseButton($actionModel) {
        if (auth()->guard('api')->user()->can("'api'.{$actionModel}.supervise")){
            return true;
        }
        return false;
    }

    public function markButton($actionModel) {
        if (auth()->guard('api')->user()->can("'api'.{$actionModel}.mark")){
            return true;
        }

        return false;
    }


    public function getActionButtons($actionModel)
    {
        return [
            'canEdit'   => $this->editButton($actionModel),
            'canDelete' => $this->deleteButton($actionModel),
            'canCreate' => $this->createButton($actionModel),
            'canPass'   => $this->passButton($actionModel),
            'canReject' => $this->rejectButton($actionModel),
            'canExport' => $this->exportButton($actionModel),
            'canSupervise' => $this->superviseButton($actionModel),
            'canMark'   => $this->markButton($actionModel)
        ];
    }
}