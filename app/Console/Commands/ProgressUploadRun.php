<?php

namespace App\Console\Commands;

use App\Models\Progress;
use App\Models\ProjectPlanCustom;
use App\Models\Upload;
use Illuminate\Console\Command;

class ProgressUploadRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'progressupload:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'wh_upload_progress 数据迁移';

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
        \DB::connection('xgwh_online')->table('wh_upload_progress')->orderBy('id')->chunk(10000, function ($projectlist)  {
            $aa =[];
            foreach ($projectlist as $k => $v) {
                //$aa['id'] = $v->id;
                $aa['pid'] = $v->pid;
                $aa['relation_id'] = $v->mid;
                $aa['uid'] = $v->uid;
                $aa['url'] = $v->url;
                $aa['filename'] = $v->filename; //原名
                $aa['file_new_name'] = $v->file_new_name;
                $aa['ext'] = $v->ext;
                $aa['type'] = $v->type; //去掉末尾 逗号
                $aa['cid'] = $v->cid;
                $aa['file_type'] = 2;
                $aa['month'] = $v->month;
                //$aa['m_progress_id'] = $v->mid;
                $aa['add_time'] = $v->add_time;
                Upload::create($aa);
            }
        });
    }

}
