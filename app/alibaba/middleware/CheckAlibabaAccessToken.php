<?php
namespace app\alibaba\middleware;

use fast\Redis;
use think\facade\Cache;

class CheckAlibabaAccessToken
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        if (!Redis::get('alibaba_access_token')) {
            return json(['status' => 'fail', 'msg' => 'ACCESS_TOKEN过期,请授权登录', 'code' => 11001]);
        }
        return $next($request);
    }
}
