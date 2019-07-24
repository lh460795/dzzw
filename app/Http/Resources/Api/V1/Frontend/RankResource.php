<?php
/**
 * 项目详细页处理
 */
namespace App\Http\Resources\Api\V1\Frontend;

use App\Http\Resources\BaseResource;
use Carbon\Carbon;
class RankResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {


        return [
            'id'=>$this->id,
            'pname'=>\App\Models\Project::where('id',$this->pid)->value('pname'),
            'pid' => $this->pid,
            's_score' => $this->s_score,
            "x_score" => $this->x_score,
            'j_score' => $this->j_score,
            'y_score' => $this->y_score,
            'd_score' => $this->d_score,
            'addtime'=>Carbon::createFromTimestamp($this->addtime)->toDateTimeString(),
            'month' => $this->month,
            'year' => $this->year,
            'type' => $this->type,
            'rank' => $this->rank
        ];
    }
}
