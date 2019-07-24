<?php

namespace App\Observers;

use App\Models\MonthScore;
use App\Models\MonthScoreHistory;
use App\Service\ScoreService;

class ScoreObservers {

    protected $_scoreService;
    public function __construct(ScoreService $scoreService)
    {
       $this->_scoreService = $scoreService;
    }

    public function created(MonthScore $monthScore)
    {
        //插入月度评分表时 插入wh_month_score_history 表
        $data['score_id'] =$monthScore->id;
        $data['pid'] =$monthScore->pid;
        $data['addtime'] =$monthScore->addtime;
        $data['month'] =$monthScore->month;
        $data['year'] =$monthScore->year;
        $data['data'] =$this->_scoreService->computeScore($monthScore->pid) ?? '';

        MonthScoreHistory::create($data);
    }
}