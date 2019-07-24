<?php

namespace App\Observers;
use App\Notifications\UserAttention;
use App\Models\Project;
use App\Models\User;
class AttentionObservers {
    public function created(Project $project)
    {
        // 通知项目持有人有人关注了
        $project->users->notify(new UserAttention($project));
    }
}