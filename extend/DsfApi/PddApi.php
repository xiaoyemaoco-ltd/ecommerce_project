<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/30
 * Time: 15:08
 */
namespace DsfApi;
use think\facade\Cache;
/*
* 拼多多公共基础类
* @Author: lovefc
* @Date: 2019-07-15 08:30:21
* @Last Modified by: lovefc
* @Last Modified time: 2019-07-30 08:36:31
*/
class PddApi{
    protected static $TOKEN_API = 'http://open-api.pinduoduo.com/oauth/token';//授权链接
    protected static $URL = 'http://gw-api.pinduoduo.com/api/router';//调用链接
    protected static $client_id; // 编号id
    protected static $client_secret; // 应用密钥
    protected static $backurl; // 回调地址
    protected static $data_type; // 接口返回数据格式
    protected static $pdd_token_file; // token授权后的json存放文件，会在实例化类的时候自动解析
    protected static $access_token; // token
    protected static $refresh_token; // 刷新token
    protected static $expires_in; // token刷新时间
    protected static $scope; // 权限列表
    protected static $owner_id; // 店铺id
    protected static $owner_name; //店铺名
    protected $code;
    // 构造函数
    public function __construct($config = '', $token_json = ''){
        if ($config) {
            $this -> configuration($config, $token_json);
            $this ->restToken();
        }
//        epre($token_json);
        if (!empty($token_json) && is_array($token_json)) {
            $this -> arrToken($token_json);
        }
    }
    // 解析配置
    public function configuration($config, $token_json){
        self::$client_id = isset($config['client_id']) ? $config['client_id'] : '';
        self::$client_secret = isset($config['client_secret']) ? $config['client_secret'] : '';
        self::$backurl = isset($config['backurl']) ? $config['backurl'] : '';
        self::$data_type = isset($config['data_type']) ? strtoupper($config['data_type']) : 'JSON';
        self::$pdd_token_file = isset($config['pdd_token_file']) ? strtoupper($config['pdd_token_file']) : '';
    }

    // 获取解析token  刷新令牌.
    public function restToken(){
        if (is_file(self::$pdd_token_file) && empty(self::$access_token)) {
            $token_json = file_get_contents(self::$pdd_token_file);
            $this -> arrToken($token_json);
        }
    }

    // token转数组
    public function arrToken($token_json){
        $config = json_decode($token_json, true);
        self::$expires_in = isset($config['expires_in']) ? $config['expires_in'] : 0;
        self::$access_token = isset($config['access_token']) ? $config['access_token'] : '';
        self::$refresh_token = isset($config['refresh_token']) ? $config['refresh_token'] : '';
        self::$scope = isset($config['scope']) ? $config['scope'] : '';
        self::$owner_id = isset($config['owner_id']) ? $config['owner_id'] : '';
        self::$owner_name = isset($config['owner_name']) ? $config['owner_name'] : '';
    }

    /**
     * 获取 access token.
     * @param      $code
     * @param null $state
     * @return mixed
     */
    public function getAccessToken(){

        if (!empty($_GET['code'])) {
            $this->setCode(trim($_GET['code']));
        }
        if (empty($this->code)) {
            $this -> error('code不能为空');
        }
        $data = array(
            "client_id" => self::$client_id,
            "code" => $this->code,
            "grant_type" => "authorization_code",
            "client_secret" => self::$client_secret,
        );
        $result = $this->post(self::$TOKEN_API, json_encode($data));
        $result1 = json_decode($result,true);
        self::$scope = $result1['scope'];
        return $result;
    }
    /**
     * @param     $token
     * @param int $expires
     * @return 创建token
     */
    public function createAuthorization($token, $expires = 86400){
        if ($token) {
            Cache::set('access_token',$token,$expires);
//            file_put_contents(self::$pdd_token_file, $token);
            $this -> restToken();
            return true;
        }
        return false;
    }

    /**
     * @param mixed $code
     *
     * @return AccessToken
     */
    public function setCode($code){
        $this->code = $code;
        return $this;
    }

    // 生成链接  执行pddapi
    public function request($method,$params=[]){
        $params = $this->paramsHandle($params);
        $this -> checkApi($method);
        $arr = array(
            'client_id' => self::$client_id,
            'type'=> $method,
            'data_type' => self::$data_type,
            'timestamp' => time(),
        );
        $data = array_merge($arr,$params);
        $data['sign'] = $this -> signature($data);
        $result = $this->curl_post(self::$URL,$data);
        return json_decode($result,true);
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
    private function signature($params){
        ksort($params);
        $paramsStr = '';
        array_walk($params, function ($item, $key) use (&$paramsStr) {
            if ('@' != substr($item, 0, 1)) {
                $paramsStr .= sprintf('%s%s', $key, $item);
            }
        });
        return strtoupper(md5(sprintf('%s%s%s', self::$client_secret, $paramsStr, self::$client_secret)));
    }
    // 提交请求
    public  function post($url, $data = ''){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $output = curl_exec($ch);
        if ($output === false) {
            $this->error(curl_error($ch));
        }
        curl_close($ch);
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
    //刷新token
    public function getNewToken($state = 1212){
        $data = array(
            "client_id" => self::$client_id,
            "client_secret" => self::$client_secret,
            "grant_type" => "refresh_token",
            "refresh_token" => self::$refresh_token,
            "state" => $state,
        );
        $data = json_encode($data);
        return $this->post(self::$TOKEN_API, $data);
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




    //打印错误
    public function error($error){
        die('CURL Error:' . $error);
    }


}
