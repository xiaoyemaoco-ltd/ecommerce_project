<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/26
 * Time: 15:19
 */
namespace app\api\controller;
use app\Request;
use think\facade\Db;
/**
 * 手机短信接口
 */
class Sms extends Api{
    /**
     * 发送验证码
     *
     * @param string $mobile 手机号
     * @param string $event 事件名称
     */
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    /**
     * 发送验证码
     *
     * @param string $mobile 手机号
     * @param string $event  事件名称
     */
    public function send(Request $request){
        $mobile = $request->post("mobile");
        $event = $request->post("event") ? $request->post("event") : 1;
        if(empty($mobile)){
            return $this->error('401','手机号不能为空!');
        }
        if(empty($event)){
            return $this->error('401','请指明操作类别!');
        }
        if(!preg_match("/1[34578]{1}\d{9}$/",$mobile)){
            return $this->error('401','手机格式错误!');
        }
        //查询最后一次发送次数
        $lasttime = Db::name('sms') -> where(['mobile'=>$mobile,'event'=>$event]) -> order('id', 'DESC') -> find();
        if ($lasttime && time() - $lasttime['send_time'] < 60) {
            return  $this->error('402','发送频繁!');
        }
//        $ipSendTotal= Db::name('sms') -> where(['ip'=> $this->request->ip()]) -> whereTime('send_time', '-1 hours') -> count();
//        if ($ipSendTotal >= 5) {
        //      return  $this->error(402,'发送频繁!');
//        }

        switch ($event){
            case 1:
                //验证手机号是否已注册
                $where = "user_name = '$mobile' or mobile = '$mobile'";
                $result = Db::name('user') -> where($where) -> count();
                if($result > 0){
                  return  $this->error('401','该手机号已经注册!');
                }else{
                    return   $this -> sms1($mobile,$event);
                }
                break;
            case 2:
                return $this -> sms1($mobile,$event);
                break;
        }
    }
    public function sms1($mobile,$type){
        $code = 123456;
        $vardata['mobile'] = $mobile;
        $vardata['varcode'] = $code;
        $vardata['event'] = $type;
        $vardata['send_time'] = time();
        $vardata['ip'] = get_client_ip();
        $varcode_result = Db::name('sms')->insert($vardata);
        if($varcode_result){
            return $this->success('200','您的验证码已发送，请注意查收!',['varcode'=>$code]);
        }else{
            return  $this->error('401','验证码发送失败!');
        }
    }

    //短信发送验证吗
    public function sms($mobile,$type){
        $code = strval(rand(100000,999999));
        //短信包验证码
        $smsapi = "http://api.smsbao.com/";
        $user = "17792416697"; //短信平台帐号
        $pass = md5("qwe123"); //短信平台密码
        $time = 5;
        switch ($type){
            case 1:
                $content="【吾境】您的验证码为{$code},您正在注册吾镜会员,打死也不要告诉别人哟,在{$time}分钟内有效。~";//要发送的短信内容
                break;
            case 2:
                $content="【吾境】您的验证码为{$code},您正在修改密码,打死也不要告诉别人哟,在{$time}分钟内有效。";//要发送的短信内容
                break;
        }

        $sendurl = $smsapi."sms?u=".$user."&p=".$pass."&m=".$mobile."&c=".urlencode($content);
        $result =file_get_contents($sendurl) ;

        if( $result == 0 ){   //发送成功
            $vardata['mobile'] = $mobile;
            $vardata['varcode'] = $code;
            $vardata['event'] = $type;
            $vardata['send_time'] = time();
            $vardata['ip'] = get_client_ip();
            $varcode_result = Db::name('sms')->insert($vardata);
            if($varcode_result){
                return  $this->success('200','您的验证码已发送，请注意查收!',['varcode'=>$code]);
            }else{
                return   $this->error('401','验证码发送失败!');
            }

        }else{//发送失败
            return $this->error(401,'验证码发送失败!');
        }
        //阿里云语音验证码
        // $host = "https://sendvoc.market.alicloudapi.com";
        // $path = "/sendvoc";
        // $method = "GET";
        // $appcode = "f4b07c5446e7459fa4f16b2e077407b5";
        // $headers = array();
        // array_push($headers, "Authorization:APPCODE " . $appcode);
        // $querys = "mobile=$mobile_num&content=$code";
        // $bodys = "";
        // $url = $host . $path . "?" . $querys;

        // $curl = curl_init();
        // curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        // curl_setopt($curl, CURLOPT_URL, $url);
        // curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        // curl_setopt($curl, CURLOPT_FAILONERROR, false);
        // curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // //curl_setopt($curl, CURLOPT_HEADER, true);
        // //如果返回信息空不输出json串, 请打开上面这行代码的注释，打印服务器返回的http响应头信息。
        // //状态码: 200 正常提交；400 参数错误；401 APPCODE错误； 403 次数用完； 500 网关内部错误。
        // if (1 == strpos("$".$host, "https://"))
        // {
        //     curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        //     curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        // }

        // $res = curl_exec($curl);
        // //print_r($res);
        // $res = json_decode($res,true);
    }

}