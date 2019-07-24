<?php

namespace App\Console\Commands;

use App\Models\MonthScore;
use App\Models\UnitAvg;
use Illuminate\Console\Command;

class UnitAvgRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unitavg:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'units_avg 数据迁移';

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
        \DB::connection('xgwh_online')->table('wh_corp_avg')->orderBy('id')->chunk(500, function ($projectlist)  {
            $aa =[];
            foreach ($projectlist as $k => $v) {
                $aa['id'] = $v->id;
                $aa['units_id'] = $v->corp_id;
                $aa['year'] = $v->year;
                $aa['addtime'] = $v->addtime;
                $aa['svg_score'] = $v->svg_score;
                $aa['rc_score'] = $v->rc_score;
                UnitAvg::create($aa);
            }
        });

    }
}
