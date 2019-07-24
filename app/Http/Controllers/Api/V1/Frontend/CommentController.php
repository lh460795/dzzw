<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Events\PublishEvent;
use App\Models\Comment;
use App\Models\Project;
use App\Models\ReadLog;
use App\Models\Reply;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Api\CommentRequest;

class CommentController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /*
     * 点评列表
     */
    public function index()
    {
        $res = Comment::getData();
        return $this->respond($res);
    }

    public function show($pid)
    {
        $pname = Project::find($pid)->name ?? 'mock';
        $data = Comment::with([
            'reply' => function($query) {
                $query->with([
                    'children' => function($q){
                        $q->where(['type' => Reply::COMMENT_TYPE])
                        ->with('toReply:id,user_name');
                    },
                    'user' => function($query) {
                        $query->with(['corp' => function($q) {
                            $q->select('id','name');
                        }]);
                    }
                ])
                ->where(['type' => Reply::COMMENT_TYPE])
                ->orderBy('created_at','desc');
            },
            'user' => function($query) {
                $query->with('corp:id,name');
            }
        ])
            ->withCount('read_log')
            ->where(['pid' => $pid])
            ->orderBy('created_at','desc')
            ->get();

        $this->addRecord($data);

        $tmp = [
            'data' => $data,
            'pname' => $pname
        ];

        return $this->respond($tmp);
    }

    /*
     * 阅读记录
     */
    public function addRecord($data)
    {
        //阅读记录
        foreach ($data as $item)
        {
            ReadLog::firstOrCreate([
                'user_id' => Auth::id(),
                'relation_id' => $item->id,
                'type' => ReadLog::COMMENT_TYPE
            ]);
        }
    }

    /*
     * 最热点评项目
     */
    public function hot()
    {
        $comments = Comment::get()->groupBy('pid')->toArray();
        $pids = array_keys($comments);
        $tmp = [];
        for($i=0;$i<count($pids);$i++) {
            $count_comment = Comment::where(['pid' => $pids[$i]])->count();
            $count_reply = Reply::where(['pid' => $pids[$i]])->count();
            $tmp[$i]['count'] = $count_reply + $count_comment;
            $tmp[$i]['pid'] = $pids[$i];
        }
        $tmp = array_slice(arraySort($tmp,'count'),0,6);
        $info = [];
        for($i=0;$i<count($tmp);$i++) {
            $data = Comment::with([
                'user' => function($query) {
                    $query->with(['corp' => function($q) {
                        $q->select('id','name');
                    }]);
                }
            ])
                ->where(['pid' => $tmp[$i]['pid']])
                ->orderBy('created_at', 'desc')
                ->get()
                ->toArray();
            $info[$i]['comment'] = $data[0];
            $info[$i]['pname'] = Project::find($tmp[$i]['pid'])->pname ?? 'mock';
            $info[$i]['total_count'] = $tmp[$i]['count'];
        }
        return $this->respond($info);
    }

    /*
     * 发布点评
     */
    public function store(CommentRequest $request)
    {
        $data = $request->all();
        $model = Comment::create($data);
        if($model) {
            $model->user = $model->user;
            $model->user->corp = $model->user->corp;
            return $this->success($model);
        }else{
            return $this->failed('操作失败');
        }
    }

    /*
     * 删除点评（连带删除回复）
     */
    public function delete()
    {
        $data = $this->request->id;
        //验证本人
        $this->authorize('delete',Comment::find($data));
        if(Comment::destroy($data)) {
            return $this->success([]);
        }else{
            return $this->failed('操作失败');
        }
    }

    /*
     * 我的点评
     */
    public function myComments()
    {
        $user = Auth::user();
        $comments = Comment::with('project:id,pname')
            ->where(['user_id' => $user->id])
            ->filter($this->request->all())
            ->get()
            ->toArray();
        $replys = Reply::with('project:id,pname')
            ->filter($this->request->all())
            ->where(['type' => Reply::COMMENT_TYPE,'user_id' => $user->id])
            ->get()
            ->toArray();

        $data = array_merge($comments,$replys);
        if(empty($data)) {
            return $this->respond([]);
        }

        $data = arraySort(array_values($data),'created_at',$this->request->sort ?? 'SORT_DESC');
        $total = count($data);
        $per_page = $this->request->per_page ?? 5 ;
        $last_page = ceil($total/$per_page);
        $page = $this->request->page ?? 1;
        $start = ($page - 1) * $per_page;
        $data = array_slice($data,$start,$per_page);

        $res = [
            'current_page' => $page,
            'data' => array_values($data),
            'last_page' => $last_page,
            'total' => $total
        ];
        return $this->respond($res);
    }
}
