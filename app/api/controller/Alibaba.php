<?php


namespace app\api\controller;


use app\Request;
use think\facade\Cache;
use fast\Redis;

class Alibaba
{
    protected $redirect_uri;
    protected $client_id;
    protected $client_secret;

    public function __construct()
    {
        $row = getplatformMsg('alibaba');
        $this->redirect_uri = $row->redirect_uri;
        $this->client_id = $row->appkey;
        $this->client_secret = $row->appsecret;
    }

    /**
     * 回调地址
     */
    public function callBack(Request $request)
    {
        $code = $request->get('code');
        $param = [
            'grant_type' => 'authorization_code',
            'need_refresh_token' => false,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => 'https://www.sxtyyd.com/api/alibaba/back',
//            'redirect_uri' => 'https://test.tianyuyide5qqcom.yxnat.softdev.top/api/alibaba/back',
            'code' => $code
        ];
        $url = 'https://gw.open.1688.com/openapi/http/1/system.oauth2/getToken/' . $this->client_id;
        $data = post_curls($url, $param);
        $data = json_decode($data, 1);
        Redis::set('alibaba_access_token', $data['access_token'], 36000);
        dump($data);
    }
}
