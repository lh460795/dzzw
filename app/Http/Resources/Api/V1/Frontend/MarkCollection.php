<?php
/**
 * 项目详细页处理
 */
namespace App\Http\Resources\Api\V1\Frontend;


use App\Models\Project;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\Api\V1\Frontend\MarkResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
class MarkCollection extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        $per_page = empty($request->input('per_page'))?15:$request->input('per_page');                                 // 每页显示数量
        if ($request->has('page')) {                  // 请求是第几页，如果没有传page数据，则默认为1
            $current_page = $request->input('page');
            $current_page = $current_page <= 0 ? 1 :$current_page;
        } else {
            $current_page = 1;
        }

        return [
            'data' =>  MarkResource::collection($this->collection),
            'total' => Project::with('tag')->filter($request->all())->count(),
            'path' =>  Paginator::resolveCurrentPath(),
            'current_page' => $current_page,
            'per_page' => $per_page,
        ];
    }
}
