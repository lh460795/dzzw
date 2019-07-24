<?php

namespace App\Console\Commands;

use App\Models\ProjectPlanCustom;
use Illuminate\Console\Command;

class PlanCustomRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plancustom:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'wh_plan_custom 数据迁移';

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
        \DB::connection('xgwh_online')->table('wh_plan_custom')->orderBy('id')->chunk(10000, function ($projectlist)  {
            $aa =[];
            foreach ($projectlist as $k => $v) {
                $aa['id'] = $v->id;
                $aa['pid'] = $v->pid;
                $aa['p_name'] = $v->p_name;
                $aa['p_value'] = $v->p_value;
                $aa['m_name'] = $this->getMname($v->m_name);
                $aa['m_value'] = $v->m_value;
                $aa['m_zrdw'] = $v->m_zrdw;
                $aa['p_year'] = $v->p_year;
                $aa['p_month'] = rtrim($v->p_month,','); //去掉末尾 逗号
                $aa['content1'] = $v->content1;
                $aa['content2'] = $v->content2;
                $aa['content3'] = $v->content3;
                $aa['content4'] = $v->content4;
                $aa['content5'] = $v->content5;
                $aa['content6'] = $v->content6;
                $aa['content7'] = $v->content7;
                $aa['content8'] = $v->content8;
                $aa['content9'] = $v->content9;
                $aa['content10'] = $v->content10;
                $aa['content11'] = $v->content11;
                $aa['content12'] = $v->content12;
                ProjectPlanCustom::create($aa);
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
