<?php
namespace App\Models\Filters;

use App\Models\Project;
use EloquentFilter\ModelFilter;

class ProjectDraftFilter extends ModelFilter
{
    /**
     * Related Models that have ModelFilters as well as the method on the ModelFilter
     * As [relationMethod => [input_key1, input_key2]].
     *
     * @var array
     */
    public $relations = [];


    public function type($type_id)
    {
        return $this->where('type', $type_id);
    }

    public function start($start) {
        return $this->where('created_at','>=', $start);
    }

    public function end($end) {
        return $this->where('created_at', '<=', $end);
    }

    public function setup()
    {
        return $this->whereIn('pro_status', [
            Project::PROJECT_NORMAL,
            Project::PROJECT_DELAY,
            Project::PROJECT_SLOW,
            Project::PROJECT_OVERDUE
        ]);
    }

}
