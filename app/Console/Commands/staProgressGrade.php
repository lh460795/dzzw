<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;

class staProgressGrade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sta:progress';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '项目进度值更新';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //基础条件 在建项目  排除 4：项目完结 5：调整项目 6：未完结项目
        $where_project = Project::getjszProject();
        //获取每个入库项目的pid
        Project::where($where_project)->select('id')->chunk(100,function ($proinfo){
            $progress =[];
            foreach ($proinfo as $k => $v) {
                //echo $v->id.'<br/>';
                $progress = [
                    'progress'=>get_progresswidth($v->id)
                ];
                Project::where('id',$v->id)->update($progress);
            }
        });
        echo 'END:' . date('Y-m-d H:i:s', time());
    }
}
