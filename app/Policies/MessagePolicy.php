<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use App\Models\CusMessage;
class MessagePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }


    public function update(User $user, CusMessage $cusMessage)
    {
        return $user->isAuthorOf($cusMessage);
    }

    public function destroy(User $user, CusMessage $cusMessage)
    {
        return $user->isAuthorOf($cusMessage);
    }
}
