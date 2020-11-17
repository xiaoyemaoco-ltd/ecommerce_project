<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/29
 * Time: 18:59
 */
namespace app\api\controller;
use think\App;
use think\Response;
use think\facade\Lang;
use think\facade\Event;
use think\facade\Config;
use think\exception\ValidateException;
use think\exception\HttpResponseException;
use think\facade\Request;
use think\facade\Session;
use think\facade\Cache;
use think\facade\Db;

/*
 * 基类控制器
 * */

class Api{
    /**
     * @var Request Request 实例
     */
    protected $request;
    /**
     * @var bool 验证失败是否抛出异常
     */
    protected $failException = false;
    /**
     * @var bool 是否批量验证
     */
    protected $batchValidate = false;
    /**
     * @var array 前置操作方法列表
     */
    protected $beforeActionList = [];
    /**
     * 无需登录的方法,同时也就不需要鉴权了.
     */
    protected $noNeedLogin = [];

    /**
     * 无需Token的方法,需登录.
     */
    protected $noNeedToken = [];

    /**
     * 默认响应输出类型,支持json/xml.
     *
     * @var string
     */
    protected $responseType = 'json';

    protected $isLogin = false;

    protected $uid;//会员ID
    protected $username;//会员账号
    /**
     * 构造方法.
     *
     * @param Request $request Request 对象
     */
    public function __construct(App $app){
        $this->request = is_null($app->request) ? \think\facade\Request::instance() : $app->request;

        // 控制器初始化
        $this->initialize();

        // 前置操作方法
        if ($this->beforeActionList) {
            foreach ($this->beforeActionList as $method => $options) {
                is_numeric($method) ?
                    $this->beforeAction($options) :
                    $this->beforeAction($method, $options);
            }
        }
    }

    /**
     * 初始化操作.
     */
    public function initialize(){
        //移除HTML标签
        $this->request->filter('trim,strip_tags,htmlspecialchars');
        $modulename = app()->http->getName();
        $controllername = strtolower($this->request->controller());
        $actionname = strtolower($this->request->action());

        $path = str_replace('.', '/', $controllername).'/'.$actionname;
        echo  $_SESSION['expiretime'];
        // 设置当前请求的URI
        $this->setRequestUri($path);
        // 检测是否需要验证登录 无登录方法跳转
        if($this -> match($this->noNeedLogin)) return true;
//        $token = input('token');
        $this -> uid = Session::get('userid')['uid'];
        if($this -> match($this->noNeedToken)){return true;};
        //检测是否登录

        if(empty($this -> uid) ){
            return false;
         }
       
        /*if(empty($token)){
            exit(json_encode($this->error('401','请登录后操作')));
        }*/
//        $filed = "b.id as uid,b.user_name as username,b.user_status,a.*";
//        $result = Db::name('user_token')
//            -> alias('a') -> join('user b','a.user_id=b.id')
//            -> where(['a.token'=>$token]) -> field($filed) -> find() ;
//        if(empty($result)){
//            exit(json_encode($this->error('401','token 有误！')));
//        }
//        if ($result['user_status'] != 1){
//            exit(json_encode($this->error('401','token不能为空！'))) ;
//        }
//        if(time() - $result['update_token_time'] > 0){
//            Db::name('user') -> where('id',$result['uid']) -> update(['1ogin_status'=>0]);
//            exit(json_encode($this->error('401','登录已过期,请重新登录！')));
//        }
//        $new_time_out = time() + 604800;//604800是七天
//        Db::name('user_token') -> where('user_id',$result['uid']) -> update(['update_token_time'=> $new_time_out]);
//        unset($result['user_status']);



//        $upload = \app\common\model\Config::upload();
//
//        // 上传信息配置后
//        Event::trigger('upload_config_init', $upload);
//
//        Config::set(array_merge(Config::get('upload'), $upload), 'upload');
    }


    //登录 token 验证
    public function logintoken($token){
        if(empty($token)){
            return $this -> error('1001','token不能为空！');
        }
        $filed = "b.id as uid,b.user_name as username,b.user_status,a.*";
        $result = Db::name('user_token')
            -> alias('a') -> join('user b','a.user_id=b.id')
            -> where(['a.token'=>$token]) -> field($filed) -> find();
        if(empty($result)){
            return $this -> error('1001','token 有误！');
        }
        if(time() - $result['update_token_time'] > 0){
            Db::name('user') -> where('id',$result['uid']) -> update(['login_status'=>0]);
            return $this -> error('1001','登录已过期,请重新登录！');
        }else{
            $new_time_out = time() + 7200;//2小时
            Db::name('user_token') -> where('user_id',$result['uid']) -> update(['update_token_time'=> $new_time_out]);
            unset($result['user_status']);
            $data2=['uid'=>$result['uid'],'username'=>$result['username']];
            Session::set('userid',$data2);
            return $this -> success('200','','');
        }
    }

    protected function token($token){
        if(empty($token)){
            return 1;
        }

        if(!empty($result)){
            if ($result['user_status'] != 1){
                return 2;
            }
            if (time() - $result['update_token_time'] > 0) {

                return 3;
            }else{

                return 5;
            }
        }else{
            return 4;
        }
    }


    //判断用户输入的验证码是否正确
   protected function check_varcode_prvite($mobile,$varcode){
        if(empty($mobile) || empty($varcode)){
            return  1;
        }
        $where['mobile']= $mobile;
        $result = Db::name('sms') -> where($where)->field("varcode,send_time")
            -> order('send_time desc') -> limit(0,1) -> find();
        //判断验证码是否正确
        if(!$result || $result['varcode'] != $varcode){
            return  2;//
        }
        //判断验证码过期
        if( time() - $result['send_time'] > 5*60){
            return  3;//
        }
    }

    /**
     * 检测当前控制器和方法是否匹配传递的数组.
     *
     * @param array $arr 需要验证权限的数组
     *
     * @return bool
     */
    protected function match($arr = []){
        $request = Request::instance();
        $arr = is_array($arr) ? $arr : explode(',', $arr);
        if (! $arr) {
            return false;
        }
        $arr = array_map('strtolower', $arr);
        // 是否存在
        if (in_array(strtolower($request->action()), $arr) || in_array('*', $arr)) {
            return true;
        }
        // 没找到匹配
        return false;
    }


    /**
     * 设置当前请求的URI.
     * @param string $uri
     */
    public function setRequestUri($uri){
        $this->requestUri = $uri;
    }
    /**
     * 操作成功返回的数据.
     *
     * @param string $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为1
     * @param string $type   输出类型
     * @param array  $header 发送的 Header 信息
     */
    protected function success($code = 1,$msg = '', $data = null,  $type = null, array $header = []){
        return  $this->result('ok',$msg, $data, $code, $type, $header);
    }

    /**
     * 操作失败返回的数据.
     *
     * @param string $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为0
     * @param string $type   输出类型
     * @param array  $header 发送的 Header 信息
     */
    protected function error($code = 0,$msg = '', $data = null,  $type = null, array $header = []){
       return $this->result("fail",$msg,$data, $code, $type, $header);
    }

    /**
     * 返回封装后的 API 数据到客户端.
     *
     * @param mixed  $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为0
     * @param string $type   输出类型，支持json/xml/jsonp
     * @param array  $header 发送的 Header 信息
     *
     * @throws HttpResponseException
     * @return void
     */
    protected function result($status,$msg, $data = null, $code = 0, $type = null, array $header = []){
        $result = [
            'status' => $status,
            'code' => $code,
            'msg'  => $msg,
            'time' => time(),
            'data' => $data,
        ];
        return json_encode($result);
    }

    /**
     * 前置操作.
     *
     * @param string $method  前置操作方法名
     * @param array  $options 调用参数 ['only'=>[...]] 或者 ['except'=>[...]]
     *
     * @return void
     */
    protected function beforeAction($method, $options = []){
        if (isset($options['only'])) {
            if (is_string($options['only'])) {
                $options['only'] = explode(',', $options['only']);
            }

            if (! in_array($this->request->action(), $options['only'])) {
                return;
            }
        } elseif (isset($options['except'])) {
            if (is_string($options['except'])) {
                $options['except'] = explode(',', $options['except']);
            }

            if (in_array($this->request->action(), $options['except'])) {
                return;
            }
        }

        call_user_func([$this, $method]);
    }

    /**
     * 设置验证失败后是否抛出异常.
     *
     * @param bool $fail 是否抛出异常
     *
     * @return $this
     */
    protected function validateFailException($fail = true){
        $this->failException = $fail;
        return $this;
    }
    

    // 判断是否手机访问
    protected function isMobile(){
        $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
        $mobile_browser = '0';
        if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
            $mobile_browser++;
        if ((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']), 'application/vnd.wap.xhtml+xml') !== false))
            $mobile_browser++;
        if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
            $mobile_browser++;
        if (isset($_SERVER['HTTP_PROFILE']))
            $mobile_browser++;
        $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
        $mobile_agents = array(
            'w3c ', 'acs-', 'alav', 'alca', 'amoi', 'audi', 'avan', 'benq', 'bird', 'blac',
            'blaz', 'brew', 'cell', 'cldc', 'cmd-', 'dang', 'doco', 'eric', 'hipt', 'inno',
            'ipaq', 'java', 'jigs', 'kddi', 'keji', 'leno', 'lg-c', 'lg-d', 'lg-g', 'lge-',
            'maui', 'maxo', 'midp', 'mits', 'mmef', 'mobi', 'mot-', 'moto', 'mwbp', 'nec-',
            'newt', 'noki', 'oper', 'palm', 'pana', 'pant', 'phil', 'play', 'port', 'prox',
            'qwap', 'sage', 'sams', 'sany', 'sch-', 'sec-', 'send', 'seri', 'sgh-', 'shar',
            'sie-', 'siem', 'smal', 'smar', 'sony', 'sph-', 'symb', 't-mo', 'teli', 'tim-',
            'tosh', 'tsm-', 'upg1', 'upsi', 'vk-v', 'voda', 'wap-', 'wapa', 'wapi', 'wapp',
            'wapr', 'webc', 'winw', 'winw', 'xda', 'xda-'
        );
        if (in_array($mobile_ua, $mobile_agents))
            $mobile_browser++;
        if (strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)
            $mobile_browser++;
        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)
            $mobile_browser = 0;
        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)
            $mobile_browser++;
        if ($mobile_browser > 0)
            return true;
        else
            return false;
    }
}
