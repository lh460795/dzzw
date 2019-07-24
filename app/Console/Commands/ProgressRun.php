<?php

namespace App\Console\Commands;

use App\Models\Progress;
use App\Models\ProjectPlanCustom;
use Illuminate\Console\Command;

class ProgressRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'progress:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'wh_m_progress 数据迁移';

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
        //INNODB 插入有点慢
        \DB::connection('xgwh_online')->table('wh_m_progress')->orderBy('id')->chunk(10000, function ($projectlist)  {
            $aa =[];
            foreach ($projectlist as $k => $v) {
                $aa['id'] = $v->id;
                $aa['pid'] = $v->pid;
                $aa['p_time'] = $v->p_time;
                $aa['p_time_old'] = $v->p_time_old;
                $aa['y_time'] = $v->y_time;
                $aa['custom_id'] = $v->custom_id;
                $aa['month'] = $v->month;
                $aa['p_year'] = $v->p_year;
                $aa['p_progress'] = $v->p_progress; //去掉末尾 逗号
                $aa['a_progress'] = $v->a_progress;
                $aa['explain'] = $v->explain;
                $aa['remark'] = $v->remark;
                $aa['p_status'] = $v->p_status;
                $aa['m_account'] = $v->m_account;
                Progress::create($aa);
            }
        });
    }

    // a_qqgz[0] 转换 0_jc_c
    // b_jsjd[0] 转换 1_jc_c
    // c_ysjd[0] 转换 2_jc_c
    public function getMname($m_name)
    {
        if (strstr($m_name, "a_qqgz")) {
            return '0_jc_c';
        }elseif(strstr($m_name, "b_jsjd")){
            return '1_jc_c';
        }elseif(strstr($m_name, "c_ysjd")){
            return '2_jc_c';
        }elseif(strstr($m_name, "_jc_c")){
            return preg_replace('/\[.*?\]/', '', $m_name);; //去掉中括号 及内容
        }
    }
}
