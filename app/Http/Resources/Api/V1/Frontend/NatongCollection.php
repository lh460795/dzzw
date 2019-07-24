<?php
/**
 * 项目列表处理
 */
namespace App\Http\Resources\Api\V1\Frontend;

use Illuminate\Http\Resources\Json\ResourceCollection;

class NatongCollection extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        //$result = parent::toArray($request);
        return [
            "data"=>NatongListResource::collection($this->collection),
            'page' => $this->resource->currentPage(), //当前页
            'per_page' => $this->resource->perPage(), //分页条数
            'total' => $this->resource->total(), //总数
         ];
    }
}
