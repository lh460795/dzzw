<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

$api = app('Dingo\Api\Routing\Router');
$api->version('V1', [
    'namespace' => 'App\Http\Controllers\Api\V1\Frontend',
    'middleware' => 'crossDomain'
], function($api) {

    $api->group([
        'middleware' => 'api.throttle',
        'limit' => config('api.rate_limits.sign.limit'),
        'expires' => config('api.rate_limits.sign.expires'),
        'prefix' => 'frontend'
    ], function($api) {
        // 登录
        $api->post('authorizations', 'AuthorizationsController@store')
            ->name('api.authorizations.store');

        // 小程序登录
        $api->post('weapp/authorizations', 'AuthorizationsController@weappStore')
            ->name('api.weapp.authorizations.store');



        $api->post('captchas', 'CaptchasController@store')
            ->name('api.captchas.store');

        //是否开启了验证码
        $api->get('authorizations/onoff', 'AuthorizationsController@onoff')
            ->name('api.authorizations.onoff');

        $api->get('auth/geetest', 'AuthorizationsController@getGeetest')
            ->name('api.authorizations.geetest');

        //项目生成pdf
        $api->get('project/pdf', 'ProjectController@exportpdf')
            ->name('api.project.pdf');


    });



    $api->group([
        'prefix' => 'frontend'
    ],function($api){
        /**
         * 用户中心
         **/
        $api->get('user', 'UserController@UserCenter');
        /**
         * 通讯录
         */
        $api->get('user/addressBook','UserController@AddressBook');
        /**
         * 登陆记录
         */
        $api->post('user/loginRecord','UserController@LoginRecord');
        /**
         * 在线用户数
         */
        $api->post('user/UserLiveNum','UserController@UserLiveNum');
        /**
         * 统计数据
         */
        $api->post('user/loginDataStatistics','UserController@loginDataStatistics');
        /**
         * 获取操作列表
         */
        $api->post('user/activityRecord','UserController@ActivityRecord');
        /**
         * 获取单位列表
         */
        $api->get('user/getUnits','UserController@getUnits');
        /**
         * 导出登陆记录xls
         */
        $api->get('user/exportActivityLog','UserController@exportActivityLog');
        /**
         * 导出登录历史记录
         */
        $api->post('user/exportLoginRecord','UserController@exportLoginRecord');
        /**
         * 导出登录历史记录统计
         */
        $api->get('user/exportLoginDataStatistics','UserController@exportLoginDataStatistics');
        /**
         * 获取操作类型
         */
        $api->get('user/getActivityType','UserController@getActivityType');
        /**
         * 项目统计仪表盘
         */
        $api->get('DataStatistics/projectStatistics','DataStatisticsController@projectStatistics');
        /**
         *项目统计表格
         */
        $api->get('DataStatistics/projectTableStatistics','DataStatisticsController@projectTableStatistics');
        /**
         *投资额统计项目数(柱状图)
         */
        $api->get('DataStatistics/investmentStatistics','DataStatisticsController@investmentStatistics');
        /**
         *投资额统计项目数(表格)
         */
        $api->get('DataStatistics/investmentTableStatistics','DataStatisticsController@investmentTableStatistics');

        //待办项目
        $api->get('pending', 'PendingController@index')
            ->name('api.pending.pending');

        $api->get('todo', 'PendingController@detail')
            ->name('api.pending.todo');
        $api->get('test', 'PendingController@test')
            ->name('api.pending.test');

        /**
         * 当前在线用户
         */
        $api->post('user/onlineUsers','UserController@onlineUsers');
        /**
         * 单位搜索组件
         */
        $api->post('unit/unitSearch','UnitController@unitSearch');
    });



    //需要登录的接口写到这个路由组里面
    $api->group([
        'prefix' => 'frontend',


         //'middleware' => 'auth:api'

    ], function ($api) {
        //项目申报
        $api->post('project/report', 'ProjectController@store')
            ->name('api.project.store');
        //项目审核
        $api->get('project/wfcheck', 'ProjectController@wfcheck')
            ->name('api.project.wfcheck');
        //项目列表
        $api->get('project/index', 'ProjectController@index')
            ->name('api.project.index');
        //项目列表
        $api->get('project/xieban', 'ProjectController@xieban')
            ->name('api.project.xieban');
        //驳回项目修改
        $api->put('project/update/{id}', 'ProjectController@update')
            ->name('api.project.update');
        //项目引导页
        $api->get('project/entry', 'ProjectController@entry')
            ->name('api.project.entry');
        //项目标准模板接口
        $api->get('project/plan/{id}', 'ProjectController@plan')
            ->name('api.project.plan');
        //项目详细页
        $api->get('project/show', 'ProjectController@show')
            ->name('api.project.show');
        //项目进度查看
        $api->get('progress/show', 'ProgressController@show')
            ->name('api.progress.show');
        //项目进度填报填写
        $api->get('progress/write', 'ProgressController@write')
            ->name('api.progress.write');
        //项目进度填报提交
        $api->post('progress/add', 'ProgressController@add')
            ->name('api.progress.add');
        //项目进度填报修改
        $api->any('progress/edit', 'ProgressController@edit')
            ->name('api.progress.edit');
        //项目进度填报审核
        $api->post('progress/pass', 'ProgressController@pass')
            ->name('api.progress.pass');
        //项目进度填报批量审核
        $api->any('progress/listpass', 'ProgressController@listpass')
            ->name('api.progress.listpass');
        //季进度查看
        $api->any('progress/progressJidu', 'ProgressController@progressJidu')
            ->name('api.progress.progressJidu');
        //立项单位待填报的进度数量
        $api->any('progress/progressCount', 'ProgressController@progressCount')
            ->name('api.progress.progressCount');
        //立项单位待填报待审的进度数量
        $api->any('progress/progressDai', 'ProgressController@progressDai')
            ->name('api.progress.progressDai');

        //项目节点内容
        $api->get('project/plancustom/{id}', 'ProjectController@plancustom')
            ->name('api.project.plancustom');
        //项目发牌
        $api->post('project/cardadd', 'ProjectController@cardadd')
            ->name('api.project.cardadd');
        //项目撤销发牌
        $api->post('project/carddel', 'ProjectController@carddel')
            ->name('api.project.carddel');
        //项目发牌记录
        $api->get('project/cardlist', 'ProjectController@cardlist')
            ->name('api.project.cardlist');
        //单个项目得分详情
        $api->get('project/score', 'ProjectController@score')
            ->name('api.project.score');

        // 续建搜索接口
        $api->post('select_project', 'ProjectController@selectProject');

        
        //点评
        $api->get('comments', 'CommentController@index');
        $api->get('comments/{pid}', 'CommentController@show');
        $api->post('comments', 'CommentController@store');
        $api->put('comments/{comment}', 'CommentController@update');
        $api->delete('comments/{id}', 'CommentController@delete');
        $api->get('myComments', 'CommentController@myComments');
        $api->get('hot', 'CommentController@hot');


        //述评
        $api->get('reviews', 'ReviewController@index');
        $api->get('reviews/{id}', 'ReviewController@show');
        $api->post('reviews', 'ReviewController@store');
        $api->put('reviews/{review}', 'ReviewController@update');
        $api->delete('reviews/{id}', 'ReviewController@delete');
        $api->get('myReviews', 'ReviewController@myReviews');
        $api->get('topReview', 'ReviewController@getTop');


        //回复
        $api->post('replys', 'ReplyController@store');
        $api->delete('replys/{id}', 'ReplyController@delete');

        //考核管理
        $api->get('lastScores','ScoreController@lastScore');
        $api->get('sponsorScores','ScoreController@sponsorScore');
        $api->get('coScores','ScoreController@coScore');
        $api->get('getUnits','ScoreController@getUnits');

        //纳统
        //系统判定列表
        $api->get('natong/systemDecision', 'NatongController@systemDecision')
            ->name('api.natong.systemDecision');
        //系统判定列表导出excel
        $api->post('natong/exportDecision','NatongController@exportDecision')
            ->name('api.natong.exportDecision');
        //推送至县市区列表
        $api->get('natong/xianshiqu', 'NatongController@xianshiqu')
            ->name('api.natong.xianshiqu');
        //推送至县市区列表导出excel
        $api->post('natong/exportXianshiqu','NatongController@exportXianshiqu')
            ->name('api.natong.exportXianshiqu');
        //未纳统项目
        $api->get('natong/weinatong', 'NatongController@weinatong')
            ->name('api.natong.weinatong');
        //未纳统项目列表导出excel
        $api->post('natong/exportWeinatong', 'NatongController@exportWeinatong')
            ->name('api.natong.exportWeinatong');
        //应统未统项目
        $api->get('natong/yingtong', 'NatongController@yingtong')
            ->name('api.natong.yingtong');
        //应统未统项目列表导出excel
        $api->post('natong/exportYingtong', 'NatongController@exportYingtong')
            ->name('api.natong.exportYingtong');
        //已纳统项目
        $api->get('natong/yinatong', 'NatongController@yinatong')
            ->name('api.natong.yinatong');
        //已纳统项目列表导出
        $api->post('natong/exportYinatong', 'NatongController@exportYinatong')
            ->name('api.natong.exportYinatong');
        //已撤销项目
        $api->get('natong/chexiao', 'NatongController@chexiao')
            ->name('api.natong.chexiao');
        //已撤销项目导出列表
        $api->post('natong/exportChexiao', 'NatongController@exportChexiao')
            ->name('api.natong.exportChexiao');
        //被提醒项目
        $api->get('natong/receive', 'NatongController@receive')
            ->name('api.natong.receive');
        //全市纳统项目
        $api->get('natong/quanshi', 'NatongController@quanshi')
            ->name('api.natong.quanshi');
        //全市纳统项目导出excel
        $api->post('natong/exportQuanshi','NatongController@exportQuanshi')
            ->name('api.natong.exportQuanshi');
        //->Middleware('CrossDomain')
        //五化办系统判定项目
        $api->get('natong/wuhuabansystem', 'NatongController@wuhuabansystem')
            ->name('api.natong.wuhuabansystem');
        //五化办系统撤销列表
        $api->get('natong/wuhuabanchexiao', 'NatongController@wuhuabanchexiao')
            ->name('api.natong.wuhuabanchexiao');
        //操作项目纳统状态
        $api->post('natong/update', 'NatongController@update')
            ->name('api.natong.update');

        //督办管理
        $api->get('supervises','SuperviseController@index');
        $api->post('supervises','SuperviseController@store');
        $api->get('mySupervises','SuperviseController@mySupervise');
        $api->get('superviseList','SuperviseController@superviseList');
        $api->post('supervise/reply','SuperviseController@reply');
        $api->put('supervise/confirm/{id}','SuperviseController@confirm');

        //督办模板
        $api->get('getTemplate','SuperviseController@getTemplate');
        $api->post('addTemplate','SuperviseController@addTemplate');
        $api->put('updateTemplate','SuperviseController@updateTemplate');
        $api->delete('deleteTemplate/{id}','SuperviseController@deleteTemplate');
        $api->get('duban/shuju','SuperviseController@shuju');



        //退出登录
        $api->post('logout', 'AuthorizationsController@destroy')
            ->name('api.authorizations.destroy');


        //关注项目类别列表
        $api->get('project', 'AttentionController@index')
            ->name('api.attention.index');
        
        $api->get('my/project', 'AttentionController@lists')
            ->name('api.attention.lists');

        $api->post('follow', 'AttentionController@follow')
            ->name('api.attention.follow');

        $api->post('unfollow', 'AttentionController@unfollow')
            ->name('api.attention.unfollow');

        //常用语管理

        $api->get('cus/index', 'CusMessageController@index')
            ->name('api.cus.index');

        $api->post('cus/create', 'CusMessageController@create')
            ->name('api.cus.create');

        $api->post('cus/update', 'CusMessageController@update')
            ->name('api.cus.unfollow');

        $api->post('cus/delete', 'CusMessageController@delete')
            ->name('api.cus.delete');

        $api->post('cus/truncate', 'CusMessageController@truncate')
            ->name('api.cus.truncate');

        //消息列表
        $api->get('notice/index', 'NotificationsController@index')
            ->name('api.notice.index');

        $api->get('notice/show', 'NotificationsController@show')
            ->name('api.notice.show');

        $api->post('notice/mark', 'NotificationsController@mark')
            ->name('api.notice.mark');

        $api->post('notice/truncate', 'NotificationsController@truncate')
            ->name('api.notice.truncate');
        $api->post('notice/delete', 'NotificationsController@delete')
            ->name('api.notice.delete');

        $api->post('notice/list', 'NotificationsController@noticelist')
            ->name('api.notice.list');


        //修改信息和密码
        $api->get('center/index', 'CenterController@index')
            ->name('api.center.index');

        $api->post('center/password', 'CenterController@editpassword')
            ->name('api.center.editpassword');

        $api->post('center/info', 'CenterController@editinfo')
            ->name('api.center.editinfo');

        $api->get('help/index', 'HelpController@index')
            ->name('api.help.index');

        $api->post('help/download', 'HelpController@download')
            ->name('api.help.download');

        //项目标识管理
        $api->get('tag/index', 'TagController@index')
            ->name('api.tag.index');

        $api->get('tag/list', 'TagController@taglist')
            ->name('api.tag.taglist');



        $api->post('tag/create', 'TagController@create')
            ->name('api.tag.create');

        $api->post('tag/update', 'TagController@update')
            ->name('api.tag.update');

        $api->post('tag/delete', 'TagController@delete')
            ->name('api.tag.delete');

        $api->post('tag/truncate', 'TagController@truncate')
            ->name('api.tag.truncate');

        $api->get('mark/index', 'MarkController@index')
            ->name('api.mark.index');

        $api->post('mark/attach', 'MarkController@mark')
            ->name('api.mark.attach');

        $api->get('mark/type', 'MarkController@typelist')
            ->name('api.tag.type');

        //草稿箱
        $api->get('draft/index', 'DraftController@index')
            ->name('api.draft.index');

        $api->get('draft/show', 'DraftController@show')
            ->name('api.draft.show');

        $api->post('draft/create', 'DraftController@create')
            ->name('api.draft.create');


        $api->post('draft/delete', 'DraftController@delete')
            ->name('api.cus.delete');

        $api->post('draft/truncate', 'DraftController@truncate')
            ->name('api.cus.truncate');

        //项目排行榜
        $api->get('rank/project', 'RankController@projectRank')
            ->name('api.rank.project');

        $api->get('rank/unit', 'RankController@unitsRank')
            ->name('api.rank.unit');

        $api->get('rank/index', 'RankController@rank')
            ->name('api.rank.index');

        //全过程管理
        $api->get('proce/index', 'ProcedureController@index')
            ->name('api.proce.index');

        //人工评分列表
        $api->get('man/index', 'ManscoreController@index')
            ->name('api.man.index');

        $api->get('man/detail', 'ManscoreController@score')
            ->name('api.man.detail');

        $api->post('man/create', 'ManscoreController@create')
            ->name('api.man.create');

        $api->post('man/update', 'ManscoreController@like')
            ->name('api.man.update');

        $api->get('help/test', 'HelpController@test')
            ->name('api.help.test');

        //短信接口
        $api->post('sms/login', 'SmsController@login')
            ->name('api.sms.login');

        $api->post('sms/send', 'SmsController@send')
            ->name('api.sms.send');

        $api->post('sms/refresh', 'SmsController@refresh')
            ->name('api.sms.refresh');

        $api->post('sms/schedule', 'SmsController@schedule')
            ->name('api.sms.schedule');

        $api->get('sms/test', 'SmsController@test')
            ->name('api.sms.test');

        // 刷新token
        $api->put('authorizations/current', 'AuthorizationsController@update')
            ->name('api.authorizations.update');
        // 删除token
        $api->delete('authorizations/delete', 'AuthorizationsController@destroy')
            ->name('api.authorizations.destroy');


//        $api->get('test', 'AttentionController@test')
//            ->name('api.attention.test1');

        //附件上传
        $api->post('upload/upload', 'UploadController@upload');
        $api->get('upload/create', 'UploadController@create');


        // 调整模块管理
        $api->group(['prefix' => 'adjust'], function($api){
            // 发起调整列表
            $api->get('lists', 'AdjustController@lists')->name('api.adjust.lists');
            // 发起停建页面
            $api->get('{id}/stop_index', 'AdjustController@stopIndex')->name('api.adjust.stop');
            // 停建提交操作
            $api->post('{id}/stop_active', 'AdjustController@stopActive')->name('api.adjust.active');
            // 停建审核页面
            $api->get('{id}/stop_audit', 'AdjustController@stopAudit')->name('api.adjust.stop_audit');
            // 申请修改项目页面
            $api->get('{id}/edit_index', 'AdjustController@editIndex')->name('api.adjust.edit_index');
            // 修改项目页面  项目信息
            $api->get('{id}/project_index', 'AdjustController@projectIndex')->name('api.adjust.project_index');
            // 节点信息
            $api->get('{pid}/plancustom', 'AdjustController@planCustomIndex');
            // 修改提交操作
            $api->post('{id}/project_edit', 'AdjustController@projectEdit')->name('api.adjust.project_edit');
            // 审核操作
            $api->post('{id}/adjust_check', 'AdjustController@adjustCheck')->name('api.adjust.adjust_check');
            // 调整中项目列表
            $api->get('adjust_ing', 'AdjustController@adjustIng')->name('api.adjust.adjust_ing');
            // 已调整列表
            $api->get('adjust_pass','AdjustController@adjustPass')->name('api.adjust.adjust_pass');
            // 待审核列表
            $api->get('adjust_dsh', 'AdjustController@adjustDsh')->name('api.adjust.adjust_dsh');
            // 进度查看 / 进度修改
            $api->match(['get', 'post'], 'progress/{customid}/month/{month}', 'AdjustController@progress');
            // 点击下一步，存储原数据到记录表
            $api->get('store', 'AdjustController@store');
            // 修改申请单
            $api->get('edit_lists', 'AdjustController@editLists');
            // 停建申请单
            $api->get('stop_lists', 'AdjustController@stopLists');
            // 查看编辑前的内容
            $api->get('history', 'AdjustController@history');
            // 编辑前进度内容查看
            $api->get('progress_history', 'AdjustController@progress_history');
            // 没有点击确定情况下，退回数据
            $api->get('{pid}/goback', 'AdjustController@goBack');
            // 数据迁移
            $api->get('qianyi','AdjustController@qianyi');
            // 附件删除
            $api->post('del_file','AdjustController@delFile');
        });


        // 完结模块管理
        $api->group(['prefix' => 'complete'], function($api){
            // 发起出库列表
            $api->get('index', 'CompleteController@index')->name('api.complete.index');
            // 发起出库申请页面
            $api->get('apply', 'CompleteController@apply')->name('api.complete.apply');
            //出库提交
            $api->post('store', 'CompleteController@store')->name('api.complete.store');
            //出库审核列表
            $api->get('lists','CompleteController@lists')->name('api.complete.lists');
            //出库审核详情页
            $api->get('audit','CompleteController@audit')->name('api.complete.audit');
            //出库审核操作
            $api->post('check','CompleteController@check')->name('api.complete.check');
            //数据迁移
            $api->get('shuju','CompleteController@shuju')->name('api.complete.shuju');
        });















    });

});
