<?php

use App\Models\Area;
use App\Models\Corp;
use App\Models\User;
use App\Work\Model\Flow;
use App\Work\Workflow;
use App\Extension\ActivityLogger\ActivityLogger;
use App\Extension\ActivityLogger\ActivityLogStatus;
use App\Models\Type;
use App\Models\ProjectPlanCustom;
use App\Models\Progress;

if (!function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param  string $path
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

// 获取当前登录用户

if (!function_exists('auth_user')) {
    /**
     * Get the auth_user.
     *
     * @return mixed
     */
    function auth_user()
    {
        return app('Dingo\Api\Auth\Auth')->user();
    }
}
if (!function_exists('dingo_route')) {
    /**
     * 根据别名获得url.
     *
     * @param string $version
     * @param string $name
     * @param string $params
     *
     * @return string
     */
    function dingo_route($version, $name, $params = [])
    {
        return app('Dingo\Api\Routing\UrlGenerator')
            ->version($version)
            ->route($name, $params);
    }
}


if (!function_exists('getFormat')) {
    /**
     * 单位转换
     * @param $num
     * @return string
     */
    function getFormat($num): string
    {
        $p = 0;
        $format = 'H/s';
        if ($num > 0 && $num < 1000) {
            $p = 0;
            return number_format($num) . ' ' . $format;
        }
        if ($num >= 1000 && $num < pow(1000, 2)) {
            $p = 1;
            $format = 'KH/s';
        }
        if ($num >= pow(1000, 2) && $num < pow(1000, 3)) {
            $p = 2;
            $format = 'MH/s';
        }
        if ($num >= pow(1000, 3) && $num < pow(1000, 4)) {
            $p = 3;
            $format = 'GH/s';
        }
        if ($num >= pow(1000, 4) && $num < pow(1000, 5)) {
            $p = 4;
            $format = 'TH/s';
        }

        if ($num >= pow(1000, 5) && $num < pow(1000, 6)) {
            $p = 5;
            $format = 'P';
        }
        $num /= pow(1000, $p);
        return number_format($num, 3) . ' ' . $format;
    }
}

if (!function_exists('arraySort')) {
    /**
     * 二维数组根据字段进行排序
     * @params array $array 需要排序的数组
     * @params string $field 排序的字段
     * @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
     */
    function arraySort($array, $field, $sort = 'SORT_DESC')
    {
        $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
    }
}


if (!function_exists('arrayMatch')) {
    /**
     * 数组数据匹配
     * @params array $array 原有数组
     * @params string $search_str 字符串
     */
    function arrayMatch($arr, $search_str)
    {

        $list = array();        // 匹配后的结果
        foreach ($arr as $key => $val) {
            if (strstr($val, $search_str) !== false) {
                array_push($list, $val);
            }
        }
        return $list;
    }
}

if (!function_exists('getTodayUnix')) {
    /**
     *以8：10为界限获取今天0点的时间戳
     * @params array $array 原有数组
     * @params string $search_str 字符串
     */
    function getTodayUnix()
    {
        $now = time();
        $base_time = strtotime(" 08:10:00");
        return $today = ($now < $base_time) ? strtotime("-1day 00:00:00") : strtotime(" 00:00:00");
    }
}


if (!function_exists('numberF')) {
    /**
     * 格式化数字
     * @params number $num 要格式的数字或科学计数法
     * @params int  $point 保留的几位小数
     */
    function numberF($num, $point)
    {
        //不是数字时返回为0
        if (!is_numeric($num)) {
            return 0;
        }

        //如果是字符串与0进行运算
        $num = is_string($num) ? ($num + 0) : $num;

        //如果是浮点型（与0运算后）,进行格式化，保留指定的小数点
        if (is_float($num)) {
            $num += 0;
            return number_format($num, $point, '.', '');
        }

        //是整型，保留0个小数点
        if (is_int($num)) {
            return number_format($num, 0, '.', '');
        }
    }
}


if (!function_exists('is_hexStr')) {
    /**
     * 16进制判断
     * @params string $str  字符串
     */
    function is_hexStr($str)
    {
        $pattern = "/^0[xX][a-fA-F0-9]{40}$/";
        if (preg_match($pattern, $str)) {
            return true;
        } else {
            return false;
        }
    }
}

if (!function_exists('arrayToObject')) {
    /**
     * 数组转换对象
     *
     * @param $e 数组
     * @return object|void
     */

    function arrayToObject($e)
    {

        if (gettype($e) != 'array') return;
        foreach ($e as $k => $v) {
            if (gettype($v) == 'array' || getType($v) == 'object')
                $e[$k] = (object)arrayToObject($v);
        }
        return (object)$e;
    }
}

if (!function_exists('objectToArray')) {
    /**
     * 对象转换数组
     *
     * @param $e StdClass对象实例
     * @return array|void
     */
    function objectToArray($e)
    {
        $e = (array)$e;
        foreach ($e as $k => $v) {
            if (gettype($v) == 'resource') return;
            if (gettype($v) == 'object' || gettype($v) == 'array')
                $e[$k] = (array)objectToArray($v);
        }
        return $e;
    }
}


if (!function_exists('formatBytes')) {
    /**
     * Notes:文件大小单位转换GB MB KB
     */
    function formatBytes($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
        return round($size, 0) . $units[$i];
    }
}

/**
 * Notes: 工作流状态
 */
function status($status)
{
    switch ($status) {
        case 0:
            return '<span class="label radius">保存中</span>';
            break;
        case 1:
            return '<span class="label radius" >流程中</span>';
            break;
        case 2:
            return '<span class="label label-success radius" >审核通过</span>';
            break;
        default: //-1
            return '<span class="label label-danger radius" >退回修改</span>';
    }
}

/**
 * Notes: 工作流按钮判断
 * @param $wf_fid
 * @param $wf_type
 * @param $status 工作流状态
 * @return string
 */
function btn($wf_fid, $wf_type, $status)
{
    $work = new workflow();
    $url = "/wf/wfcheck?wf_type={$wf_type}&wf_title=2&wf_fid={$wf_fid}";
    $url_star = "";

    $uid = auth('api')->user()->id ?? 1;
    $role = auth('api')->user()->roles[0]->id ?? 1; //角色id
    //dump(auth('member')->user()->roles[0]->id);
    switch ($status) {
        case 0:
            return '<span class="btn  radius size-S" onclick=layer_show(\'发起工作流\',"' . $url_star . '","450","350")>发起工作流</span>';
            break;
        case 1:
            $st = 0;
            $flowinfo = $work->workflowInfo($wf_fid, $wf_type, ['uid' => $uid, 'role' => $role]);

            if ($flowinfo != -1) {
                $user = explode(",", $flowinfo['status']['sponsor_ids']);
                if ($flowinfo['sing_st'] == 0) {
                    if ($flowinfo['status']['auto_person'] == 3 || $flowinfo['status']['auto_person'] == 4) {
                        if (in_array($uid, $user)) {
                            $st = 1;
                        }
                    }
                    if ($flowinfo['status']['auto_person'] == 5) {
                        if (in_array($role, $user)) {
                            $st = 1;
                        }
                    }
                } else {
                    if ($flowinfo['sing_info']['uid'] == $uid) {
                        $st = 1;
                    }
                }
            } else {
                return '<span class="btn  radius size-S">无权限</span>';
            }
            if ($st == 1) {
                return "<a class='btn  radius size-S' href='{$url}'>审核</a>";
            } else {
                return '<span class="btn  radius size-S">无权限</span>';
            }

        case 100:
            echo "<span class='btn btn-primary' onclick=layer_show('代审','{$url}&sup=1','850','650')>代审</span>";
            break;

            break;
        default:
            return '';
    }
}

/**
 *查看用户是否具有市级权限
 */
function isCityUser()
{
    $user_id = \Auth::guard('admins')->id();
    $user = User::with('role')->find($user_id)->toArray();
    $role_ids = array_column($user['role'], 'id');
    //根据后台角色判断是否为市级用户
    if (!in_array(config('role.city_role_id'), $role_ids)) {
        return false;
    } else {
        return true;
    }

}

/*
 *获取组织架构
 */
function getCorps()
{
    $user_id = \Auth::guard('admins')->id();
    $user = \App\Models\AdminUser::with('role')->find($user_id)->toArray();
    $role_ids = array_column($user['role'], 'id');
    //判断是否为市级用户
    if (in_array(config('role.city_role_id'), $role_ids)) {
        $corps = Corp::where(['parent_id' => 0])->get()->toArray();
    } else {
        $corps = Corp::where(['id' => $user['corp_id']])->get();
    }

    return $corps;
}

if (!function_exists('getYear')) {
    /**
     * 当前年份为开始年份 获取 10年列表
     * @return string
     */
    function getYear($year_now)
    {
        $opation_s = [];
        $opation_e = [];
        for ($i = 1; $i < 5; $i++) {
            $year = $year_now - $i;
            $opation_s[$i] = $year;
        }
        for ($i = 0; $i < 10; $i++) {
            $year = $year_now + $i;
            $opation_e[$i] = $year;
        }
        $opation = array_sort_recursive(array_merge($opation_s, $opation_e));
        return $opation;
    }
}

if (!function_exists('getFenguan')) {
    /**
     * 分管列表 这里实际取的是工作流表
     * @return string
     */
    function getFenguan()
    {
        $result = Flow::where('type', 'project')->select('id', 'flow_name')->orderBy('sort_order', 'asc')->get()->toArray();
        return $result;
    }
}

if (!function_exists('getAreaAll')) {
    /**
     * 孝感所有地区列表
     * @return string
     */
    function getAreaAll()
    {
        $result = Area::select('id', 'aname')->orderBy('sort', 'asc')->get()->toArray();
        return $result;
    }
}

if (!function_exists('assocUnique')) {
    /**
     * 二维数组按照指定键值去重
     * @param $arr 需要去重的二维数组
     * @param $key 需要去重所根据的索引
     * @return mixed
     */
    function assocUnique($arr, $key)
    {
        $tmp_arr = array();
        foreach ($arr as $k => $v) {
            if (in_array($v[$key], $tmp_arr)) {  //搜索$v[$key]是否在$tmp_arr数组中存在，若存在返回true
                unset($arr[$k]);
            } else {
                $tmp_arr[] = $v[$key];
            }
        }
        sort($arr); //sort函数对数组进行排序
        return $arr;
    }
}

if (!function_exists('isJsonStr')) {
    /**
     * Notes:判断字符串是否是合法的 json 字符串
     */
    function isJsonStr($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

}

function activitys(string $logName = null): ActivityLogger
{
    $defaultLogName = config('activitylog.default_log_name');

    $logStatus = app(ActivityLogStatus::class);


    return app(ActivityLogger::class)
        ->useLog($logName ?? $defaultLogName)
        ->setLogStatus($logStatus);

}

if (!function_exists('reduceArray')) {
    /**
     * Notes:多维数组变为一维数组
     */
    function reduceArray($array)
    {
        $return = [];
        array_walk_recursive($array, function ($x) use (&$return) {
            $return[] = $x;
        });
        return $return;
    }
}

if (!function_exists('get_type_name')) {
    /**
     * 根据type id 获取 type 名称
     **/
    function get_type_name($type)
    {
        return Type::get_type_name($type);
    }
}
/**
 * 根据xieban xie_fuze 原始数据 拼接成前端要的格式
 **/
function get_xieban_list($xieban, $xie_fuze)
{
    $xieban_list = [];
    $xieban_array = explode('|', $xieban);
    $xie_fuze_array = explode('|', $xie_fuze);
    foreach ($xieban_array as $k => $value) {
        $xieban_list[$k]['cooprateCorp'] = (int)$value;
        $xieban_list[$k]['name'] = \App\Models\Unit::getName($value);
        $xieban_list[$k]['cooprateMan'] = $xie_fuze_array[$k];
    }
    return $xieban_list;
}

// 根据数字获取项目类型
function get_pro_type($type)
{
    if ($type == 1) {
        return "中省级";
    } elseif ($type == 2) {
        return "市级";
    } elseif ($type == 3) {
        return "县级";
    }
}

//获取项目类型 1,2,3,4
function get_pro_kind($type)
{
    if ($type == 1) {
        return "政府投资类";
    } elseif ($type == 2) {
        return "招商引资类";
    } elseif ($type == 3) {
        return "深化改革类、社会事业类";
    } else {
        return "其他类";
    }
}

//集合分页
function paginateCollection($collection, $perPage = 7, $pageName = 'page', $fragment = null)
{
    $currentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage($pageName);
    $currentPageItems = $collection->slice(($currentPage - 1) * $perPage, $perPage)->values();
    parse_str(request()->getQueryString(), $query);
    unset($query[$pageName]);
    $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
        $currentPageItems,
        $collection->count(),
        $perPage,
        $currentPage,
        [
            'pageName' => $pageName,
            'path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(),
            'query' => $query,
            'fragment' => $fragment
        ]
    );

    return $paginator;
}

function getthemonth($date)
{
    $flag = 0;
    $firstday = date('Y-m-01', strtotime($date));
    $lastday = date('Y-m-d', strtotime("$firstday +1 month -1 day"));
    $last_2day = date('Y-m-d', strtotime("$firstday +1 month -2 day")); //倒数第二天
    $last_3day = date('Y-m-d', strtotime("$firstday +1 month -3 day")); //倒数第三天
    $last_4day = date('Y-m-d', strtotime("$firstday +1 month -4 day")); //倒数第四天
    $last_5day = date('Y-m-d', strtotime("$firstday +1 month -5 day")); //倒数第五天
    $last_6day = date('Y-m-d', strtotime("$firstday +1 month -6 day")); //倒数第六天
    $last_7day = date('Y-m-d', strtotime("$firstday +1 month -7 day")); //倒数第七天
    //当前日期 在倒数第7天 倒数第三天 最后一天
    $arritem = array($last_7day);
    if (in_array($date, $arritem)) {
        $flag = 1; //在设置范围内
    }
    return array($firstday, $lastday, $flag);
}

function get_progresswidth($pid)
{
    //计算打勾的节点数
    // $total = M('plan_custom')->where("pid={$pid} and p_month<>''")->count();
    //每个打了勾节点所占百分比
    // $jiedian_per = 100/$total;
    //计算每个勾所占节点中的百分比 X 对应勾所填进度百分比 再相加
    //节点信息
    $jd = ProjectPlanCustom::where(['pid' => $pid])->where('p_month', '!=', '')->select('p_month')->get();
    $month_num = 0;
    foreach ($jd as $k => $v) {
        //一个节点的打勾数，同一个节点中有几个','就有几个地方打过勾
        $new_arr = explode(',', $v['p_month']);

        $_month_num = count(explode(',', $v));

        $_month_num = $_month_num ? $_month_num : 0;
        //每个节点，每个勾所占节点的百分比
        $month_num += (int)$_month_num;
    }
    $per_score = $month_num ? 100 / $month_num : 0;
    $progress = Progress::where(['pid' => $pid])->where('p_status', '!=', '5')->get()->toArray();
//    dump($progress);
    $scores = array();
    foreach ($progress as $k => $v) {
        if ($v['id']) {
            $_score = $per_score * ($v['p_status'] / 4);
            $scores[$v['custom_id']][$v['month']]['score'] = $_score;
            $scores[$v['custom_id']][$v['month']]['p_id'] = $v['id'];
        }
    }
    $total_score = 0;
    foreach ($scores as $k => $v) {
        foreach ($v as $k2 => $v2) {
            $total_score += $v2['score'];
        }
    }

    $result = round($total_score, 1);
    if ($result > 100) {
        $result = 100;
    }

    return $result;
}

/**
 * 返回项目状态
 * @param type $status 0:正常  1：进展缓慢 2：严重滞后 3：逾期  4：项目完结 5：调整项目 6：提前完成
 * @return type
 */
function get_pro_status($status)
{
    $statuslist = [
        '0' => '进展正常',
        '1' => '进展缓慢',
        '2' => '严重滞后',
        '3' => '逾期',
        '4' => '项目完结',
        '5' => '调整项目',
        '6' => '项目未完结',
        '7' => '申请调整',
        '8' => '申请完结'
    ];
    $result = isset($statuslist[$status]) ? $statuslist[$status] : '';
    return $result;
}

/**
 * 项目进度 class
 * @param type $status
 * @return string
 */
function get_progressclass($status)
{
    $class = '';
    switch ($status) {
        case 0:
            $class = '';
            break;
        case 1:
            $class = 'yc';
            break;
        case 2:
            $class = 'tz';
            break;
        case 3:
            $class = 'yq';
            break;
        default:
            $class = '';
    }
    return $class;
}

//字符串去重
function str_unique($string, $fuhao = ',')
{
    if ($string != '') {
        $array_str = explode($fuhao, $string);
        return implode(',', array_unique($array_str));
    } else {
        return '';
    }
}

/**
 *  判断两个日期之间相差多少个月份
 * @param unknown $date_end 为结束日期  例 2018-12
 * @param unknown $date_start 为开始日期  例 2017-11
 * @param string $tags 日期间隔符号
 * @return
 */
function getMonthNum($date_end, $date_start, $tags = '-')
{
    $date_end = explode($tags, $date_end);
    $date_start = explode($tags, $date_start);
    return abs($date_end[0] - $date_start[0]) * 12 - $date_start[1] + abs($date_end[1]);
}

if (!function_exists('fileurl_replace')) {
    /**
     * 根据type id 获取 type 名称
     **/
    function fileurl_replace($filelist)
    {
        foreach ($filelist as $key => $val) {
            $filelist[$key]['url'] = "192.168.0.203/" . $val['url'];
        }
        return $filelist;
    }
}

/**
 * @param $units_id
 * 单位id 获取单位名称
 */
function getUnits($units_id)
{
//通过单位 units_id获取单位
    $units_name = \App\Models\Unit::getName($units_id);
    $units_name = $units_name ?? '';
    return $units_name;
}

function getNatongStatus($status)
{
    //0未纳；1应纳；2已纳；3正在申报；4等待申报；5资料不全；6后期纳统；7其他；8等待核实，9系统判定未纳统，10系统判定应统未统，11系统判定疑似未纳统，12、在系统判定的3种情况下取消标记，13、系统判定未纳统人工标记未纳统，14、系统判定疑似未纳统人工判定未纳统'
    if ($status == 0) {
        return '未纳统';
    } else if ($status == 1) {
        return '应统未统';
    } else if ($status == 2) {
        return '已纳统';
    } else if ($status == 3) {
        return '应统未统';
    } else if ($status == 4) {
        return '应统未统';
    } else if ($status == 5) {
        return '应统未统';
    } else if ($status == 6) {
        return '应统未统';
    } else if ($status == 7) {
        return '应统未统';
    } else if ($status == 8) {
        return '应统未统';
    } else if ($status == 9) {
        return '未纳统';
    } else if ($status == 10) {
        return '应统未统';
    } else if ($status == 11) {
        return '疑似未纳统';
    } else if ($status == 12) {
        return '取消标记';
    } else if ($status == 13) {
        return '未纳统';
    } else if ($status == 14) {
        return '未纳统';
    } else {
        return '未纳统';
    }
}

function get_plan_status($res)
{
    if ($res == 1) {
        return '25%';
    } else if ($res == 2) {
        return '50%';
    } else if ($res == 3) {
        return '75%';
    } else if ($res == 4) {
        return '100%';
    } else {
        return '0%';
    }
}