<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TenancyRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenancy:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成各分库的数据';

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
        $dbs = \DB::connection('system')->table('db')->select('db')->get();
        //$db = collect($db)->toArray();
        foreach ($dbs as $k=>$v) {
            config(['database.connections.tenant.database' => $v->db]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
            $exitCodes = $this->call(
                'migrate',
                []
            );

            $exitCodes = $this->call(
                'db:seed',
                []
            );
        }
    }
}
