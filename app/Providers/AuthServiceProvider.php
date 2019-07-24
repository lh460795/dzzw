<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\Reply;
use App\Models\Review;
use App\Policies\DeletePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        \App\Models\CusMessage::class => \App\Policies\MessagePolicy::class,
        Comment::class => DeletePolicy::class,
        Review::class => DeletePolicy::class,
        Reply::class => DeletePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
