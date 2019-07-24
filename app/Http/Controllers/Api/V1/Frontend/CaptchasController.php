<?php

namespace App\Http\Controllers\Api\V1\Frontend;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\Controller;
use Gregwar\Captcha\CaptchaBuilder;
class CaptchasController extends Controller
{
    public function store(Request $request, CaptchaBuilder $captchaBuilder) {
        $key = 'captcha-'.str_random(15);
        $captcha = $captchaBuilder->build();
        $expiredAt = now()->addMinutes(2);
        \Cache::put($key, ['code' => $captcha->getPhrase()], $expiredAt);

        $result = [
            'captcha_key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
            'captcha_image_content' => $captcha->inline(),
            'onoff' => $status = (int) (option('验证码开关')),
        ];

        return $this->success($result);
    }
}
