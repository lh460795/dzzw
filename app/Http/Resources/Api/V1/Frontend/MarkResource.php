<?php
/**
 * 项目详细页处理
 */
namespace App\Http\Resources\Api\V1\Frontend;

use App\Http\Resources\BaseResource;
use App\Models\Type;
use Carbon\Carbon;

class MarkResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        //dd($this->resource);
//        return $this->filterFields([
//
//        ]);

        return [
            'id'=>$this->id,
            'units'=>\App\Models\Unit::where('id',$this->units_id)->value('name'),
            'type' => Type::where('id', $this->type)->value('name'),
            'tag' => $this->tag,
            "pname" => $this->pname,
            'fen_uid' => \Auth::guard('api')->user()->username,
            "pro_status" => $this->pro_status,
            'created_at'=>$this->created_at,
        ];
    }
}
