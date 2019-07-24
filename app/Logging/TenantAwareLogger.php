<?php



namespace App\Logging;

use Monolog\Logger;
use Illuminate\Support\Carbon;
use Monolog\Handler\StreamHandler;

class TenantAwareLogger
{
    /**
     * Create a custom Monolog instance and pipe logs to the tenant directory.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $log = new Logger('tenant');
        $level = $log->toMonologLevel($config['level'] ?: 'debug');
        $distirct = request()->input('district');
        $db_id = \DB::connection('system')->table('district')->where('district', $distirct)->value('db_id');
        $db_name = \DB::connection('system')->table('db')->where('id', $db_id)->value('db');
        $directoryPath = 'app/tenancy/tenants/' . $db_name.'/' ;
        $logPath = storage_path($directoryPath . 'logs/' . $config['level'] . '_' . Carbon::now()->toDateString() . '.log');
        $log->pushHandler(new StreamHandler($logPath, $level, false));
        return $log;
    }
}
