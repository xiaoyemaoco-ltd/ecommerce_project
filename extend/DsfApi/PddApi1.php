<?php
namespace DsfApi;
use Hanson\Foundation\Http;
class PddApi1{
    protected  $clientId;
    protected  $clientSecret;
    protected  $redirectUrl;
    protected  $memberType;
    public $needToken = false;
    /** @var string 拼多多接口地址 */
    const URL = 'http://gw-api.pinduoduo.com/api/router';
    /** @var string 获取access_token地址 */
    const TOKEN_API = 'http://open-api.pinduoduo.com/oauth/token';
    /** @var array 获取code地址 */
    const AUTHORIZE_API_ARR = [
        'MERCHANT' => 'https://mms.pinduoduo.com/open.html?',
        'H5'       => 'https://mai.pinduoduo.com/h5-login.html?',
        'JINBAO'   => 'https://jinbao.pinduoduo.com/open.html?',
    ];

    /** @var array 拼多多请求接口是否需要授权 */
    protected static $scope; // 权限列表


    /** @var int 商品编辑请求失败状态 */
    const GOODS_UPDATE_COMMIT_FAIL_STATUS = 3;
    /** @var float|int 拼多多允许上传的图片大小 */
    const OPEN_PIN_DUO_DUO_UPLOAD_IMG_SIZE = 1024 *1024;

    /** @var float|int 应用免费试用时间 */
    const SERVICE_PROBATION_TIME = 15 * 24 * 3600;

    /** @var string 购买或续费应用入口 */
    const SERVICE_BUY_ENTRANCE = 'https://mms.pinduoduo.com/service-market/service-detail?detailId=411';
    /** @var string 服务市场入口 */
    const SERVICE_MARKET_ENTRANCE = 'https://mms.pinduoduo.com/service-market/';
    /**
     * @throws \Exception
     * @author: SWZ
     * @time: 14:16
     * @date: 2019/10/15
     * @describe:
     */
    public function __construct($config){
        if(empty($config)){
            return false;
        }
        $this -> clientId = $config['clientId'];
        $this -> clientSecret = $config['clientSecret'];
        $this -> redirectUrl = $config['redirectUrl'];
        $this -> memberType = $config['memberType'];
    }
    /**
     * 生成签名
     * @param $params
     * @return string
     * @author: SWZ
     * @time: 17:46
     * @date: 2019/10/22
     * @describe:
     */
    private function signature($params){
        ksort($params);
        $paramsStr = '';
        array_walk($params, function ($item, $key) use (&$paramsStr) {
            if ('@' != substr($item, 0, 1)) {
                $paramsStr .= sprintf('%s%s', $key, $item);
            }
        });
        return strtoupper(md5(sprintf('%s%s%s', $this->clientSecret, $paramsStr, $this->clientSecret)));
    }

    /**
     * 接口请求
     * @param $method
     * @param $params
     * @param string $access_token
     * @param string $data_type
     * @return mixed|string
     * @author: SWZ
     * @time: 17:49
     * @date: 2019/10/22
     * @describe:
     */
    public function request($method,$accessToken = '',$params = [],$data_type = 'JSON'){
        $params = $this->paramsHandle($params);
        // 检测是否有权限
        if(in_array($method,self::$scope)){
            $params['access_token'] = $accessToken;
        }
        $params['client_id'] = $this->clientId;
        $params['sign_method'] = 'md5';
        $params['type'] = $method;
        $params['data_type'] = $data_type;
        $params['timestamp'] = strval(time());
        $params['sign'] = $this->signature($params);
        $result = $this->curl_post(self::URL,$params);
//        $response = call_user_func_array([$http, 'post'], [self::URL, $params]);
        $responseBody = strval($result);
        return strtolower($data_type) == 'json' ? json_decode($responseBody, true) : $responseBody;
    }

    // 检测是否有接口权限
    public function checkApi($name){
        if (!self::$scope) {
            return true;
        }
        if (!in_array($name, self::$scope)) {
            die("没有{$name}接口调用权限");
        }
    }

    /**
     * @param array $params
     *
     * @return array
     */
    protected function paramsHandle(array $params){
        array_walk($params, function (&$item) {
            if (is_array($item)) {
                $item = json_encode($item);
            }
            if (is_bool($item)) {
                $item = ['false', 'true'][intval($item)];
            }
        });

        return $params;
    }


    /**
     * 跳转到拼多多授权页面获取code
     * @param string $state
     * @param null $view
     * @author: SWZ
     * @time: 17:32
     * @date: 2019/10/22
     * @describe:
     */
    public function authorizationRedirect($state = 'state', $view = null){
        $url = $this->authorizationUrl($state, $view);
        return $url;
        header('Location:'.$url);
    }



    /**
     * 获取拼多多授权登录url
     * @param null $state
     * @param null $view
     * @return string
     * @author: SWZ
     * @time: 17:33
     * @date: 2019/10/22
     * @describe:
     */
    public  function authorizationUrl($state = null, $view = null){
        return self::AUTHORIZE_API_ARR[strtoupper($this -> memberType)].http_build_query([
                'client_id'     => $this -> clientId,
                'response_type' => 'code',
                'state'         => $state,
                'redirect_uri'  => $this -> redirectUrl,
                'view'          => $view,
            ]);
    }
    /**
     * 获取access_token
     * @param null $code
     * @param null $state
     * @return mixed
     * @author: SWZ
     * @time: 17:32
     * @date: 2019/10/22
     * @describe:
     */
    public function getAccessToken($code = null, $state = null){
        return $this->token([
            'client_id'     => $this -> clientId,
            'client_secret' => $this -> clientSecret,
            'grant_type'    => 'authorization_code',
            'code'          => $code ?: $_GET['code'],
            'redirect_uri'  => $this->redirectUrl,
            'state'         => $state,
        ]);
    }

    /**
     * 使用refresh_token刷新access_token
     * @param $refreshToken
     * @param null $state
     * @return mixed
     * @author: SWZ
     * @time: 17:34
     * @date: 2019/10/22
     * @describe:
     */
    public function refreshToken($refreshToken, $state = null){
        return $this->token([
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'state'         => $state,
        ]);
    }

    public function token($params){
        $arr_header[] = "Content-Type:application/json";
        $result = $this -> http_request_post(self::TOKEN_API,json_encode($params),true,$arr_header);
        $data = ['uid'=>(int)$_GET['state'],'pddinfo'=>$result];
        $result1 = json_decode($result,true);
        self::$scope = $result1['scope'];
        return $data;
    }


    public function http_request_post($url,$data = null,$json = false,$arr_header = null){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (!empty($data)){
            if ($json && is_array($data)) {
                $data = json_encode($data);
            }
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if(!empty($arr_header)){
            curl_setopt($curl, CURLOPT_HTTPHEADER, $arr_header);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        // echo curl_getinfo($curl);
        curl_close($curl);
        unset($curl);
        return $output;
    }

    public function curl_post($url , $data=array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        // POST数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // 把post的变量加上
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
    //打印错误
    public function error($error){
        die('CURL Error:' . $error);
    }

}
