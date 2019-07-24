<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Dingo\Api\Exception\ValidationHttpException;
use Dingo\Api\Facade\API;
use App\Traits\Api\ApiResponse;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    use ApiResponse;
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        \Carbon\Carbon::setLocale('zh');  //carbon本地化
        \Schema::defaultStringLength(191);//数据库兼容问题

        \DB::listen(
            function ($sql) {
                foreach ($sql->bindings as $i => $binding) {
                    if ($binding instanceof \DateTime) {
                        $sql->bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                    } else {
                        if (is_string($binding)) {
                            $sql->bindings[$i] = "'$binding'";
                        }
                    }
                }

                // Insert bindings into query
                $query = str_replace(array('%', '?'), array('%%', '%s'), $sql->sql);

                $query = vsprintf($query, $sql->bindings);

                // Save the query to file
                $logFile = fopen(
                    storage_path('logs' . DIRECTORY_SEPARATOR . date('Y-m-d') . '_query.log'),
                    'a+'
                );
                fwrite($logFile, date('Y-m-d H:i:s') . ': ' . $query . PHP_EOL);
                fclose($logFile);
            }
        );

        //\App\Models\Project::observe(\App\Observers\AttentionObservers::class);
        \App\Models\CusMessage::observe(\App\Observers\MessageObservers::class);
        \App\Models\MonthScore::observe(\App\Observers\ScoreObservers::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        \API::error(function (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('未查询到数据');
        });

        \API::error(function (ValidationHttpException $exception) {
            foreach ($exception->getErrors()->toArray() as $key => $value){
                return $this->failed($value[0],422);
            }
        });

        \API::error(function (ValidationException $exception) {
            foreach ($exception->errors() as $key => $value){
                return $this->failed($value[0],422);
            }
        });
    }
}
