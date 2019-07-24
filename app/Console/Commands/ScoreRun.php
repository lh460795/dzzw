<?php

namespace App\Console\Commands;

use App\Models\MonthScore;
use Illuminate\Console\Command;

class ScoreRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'score:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'wh_month_score数据迁移';

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
        \DB::connection('xgwh_online')->table('wh_month_score')->orderBy('id')->chunk(10000, function ($projectlist)  {
            $aa =[];
            foreach ($projectlist as $k => $v) {
                $aa['id'] = $v->id;
                $aa['pid'] = $v->pid;
                $aa['s_score'] = $v->s_score;
                $aa['x_score'] = $v->x_score;
                $aa['j_score'] = $v->j_score;
                $aa['y_score'] = $v->y_score;
                $aa['d_score'] = $v->d_score;
                $aa['addtime'] = $v->addtime;
                $aa['month'] = $v->month;
                $aa['year'] = $v->year;
                $aa['remark'] = $v->remark;
                $aa['type'] = $v->type;
                MonthScore::create($aa);
            }
        });

    }
}
