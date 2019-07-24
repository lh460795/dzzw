<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use App\Events\PublishEvent;
use App\Http\Requests\Api\ReviewRequest;
use App\Models\ReadLog;
use App\Models\Reply;
use App\Models\Review;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /*
     * 述评列表
     */
    public function index()
    {
        $per_page = $this->request->per_page ?? 6;
        $data = Review::with([
            'user' => function($query) {
                $query->with(['corp' => function($q) {
                    $q->select('id','name');
                }]);
            }
        ])
        ->where(['is_top' => 0])
        ->orderBy('fresh_time','desc')
        ->paginate($per_page);

        $this->addRecord($data);

        return $this->respond($data);
    }


    /*
     * 置顶述评
     */
    public function getTop()
    {
        $data = Review::where(['is_top' => 1])->first();
        return $this->respond($data);
    }

    /*
     * 增加阅读记录
     */
    public function addRecord($data)
    {
        foreach ($data->items() as $item)
        {
            ReadLog::firstOrCreate([
                'user_id' => Auth::id(),
                'relation_id' => $item->id,
                'type' => ReadLog::REVIEW_TYPE
            ]);
        }
    }

    public function show($id)
    {

        if($this->request->model == 'reply') {
            $reply = Reply::find($id);
            if($reply->parent_id) {
                $id = $reply->parent->reply_id;
            }else{
                $id = $reply->reply_id;
            }
        }

        $data =  Review::with([
                'reply' => function($query) {
                    $query->with([
                        'children' => function($q){
                            $q->where(['type' => Reply::REVIEW_TYPE])
                              ->with('toReply:id,user_name');
                        },
                        'user' => function($query) {
                            $query->with(['corp' => function($q) {
                                $q->select('id','name');
                            }]);
                        }
                    ])
                        ->where(['type' => Reply::REVIEW_TYPE])
                        ->orderBy('created_at','desc');
                },
                'user' => function($query) {
                    $query->with(['corp' => function($q) {
                        $q->select('id','name');
                    }]);
                }
            ])
            ->withCount('read_log')
            ->find($id);


        return $this->respond($data);

    }

    public function update( Review $review)
    {
        $review->update($request->all());

        return response()->json($review, 200);
    }

    /*
     * 发布述评
     */
    public function store(ReviewRequest $request)
    {
        $data = $request->all();
        $model = Review::create($data);
        if($model) {
            return $this->success($model);
        }else{
            return $this->failed('操作失败');
        }
    }

    /*
     * 删除述评（连带删除回复）
     */
    public function delete()
    {
        $data = $this->request->id;
        $this->authorize('delete',Review::find($data));
        if(Review::destroy($data)) {
            return $this->success([]);
        }else{
            return $this->failed('操作失败');
        }
    }

    /**
     * 我的述评
     */
    public function myReviews()
    {
        $user = Auth::user();
        $comments = Review::filter($this->request->all())
            ->where(['user_id' => $user->id])
            ->orderBy('created_at','desc')
            ->get()
            ->toArray();
        $replys = Reply::filter($this->request->all())
            ->where(['type' => Reply::REVIEW_TYPE,'user_id' => $user->id])
            ->orderBy('created_at','desc')
            ->get()
            ->toArray();

        $data = array_merge($comments,$replys);
        if(empty($data)) {
            return $this->respond([]);
        }

        $data = arraySort(array_values(array_merge($comments,$replys)),'created_at',$this->request->sort ?? 'SORT_DESC');
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
