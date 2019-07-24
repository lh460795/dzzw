<?php



namespace App\Models;

use Auth;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Project;
use EloquentFilter\Filterable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable {
        notify as protected laravelNotify;
    }

    use HasRoles;

    use Filterable;
    
    public function notify($instance)
    {
        // 如果要通知的人是当前用户，就不必通知了！
//        if ($this->id == Auth::id()) {
//            return;
//        }
        $this->increment('notification_count');
        $this->laravelNotify($instance);
    }

    /**
     * The table name.
     *
     * @var string
     */
    protected $table = 'users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'email', 'password', 'phone'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'avatar'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getAvatarAttribute()
    {
        return \Avatar::create($this->username)->toBase64();
    }

    public function unit(){

        return $this->hasOne('App\Models\Unit','id','units_id');

    }

    public function project()
    {
        return $this->belongsToMany(Project::class, 'project_users');
    }

    public function role()
    {
        return $this->belongsToMany ('App\Models\Role','model_has_roles','model_id')
                ->where('type',2);
    }

    public function roles()
    {
        return $this->belongsToMany ('App\Models\Role','model_has_roles','model_id')
                    ->where('type',2)
                    ->orderBy('level_id','desc');
    }

    public function corp()
    {
        return $this->belongsTo('App\Models\Corp','corp_id','id');
    }

    public function parent_corp()
    {
        return $this->belongsTo('App\Models\Corp','parent_corp','id');
    }


    public function isAuthorOf($model) {
        return $this->id == $model->user_id;
    }


    // 分管领导名字
    public static function getUserName($uid){
        return self::where(['id'=>$uid])->value('username');
    }
    //关联地区表 1对1
    public function area_info(){

        return $this->hasOne('App\Models\Area','id','area_id');

    }
}
