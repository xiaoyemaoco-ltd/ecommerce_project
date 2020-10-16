<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/31
 * Time: 14:10
 */
namespace app\index\controller;

use app\BaseController;
use app\Request;
use fast\Redis;
use think\View;

class Swoftl extends BaseController{

    public function index(Request $request){
        $access_token = $request->request('token');
        $token = decrypt($access_token, env(app.app_key));
        if ($token) Redis::set('alibaba_access_token', $access_token, 36000);
        return $this->fetch();
    }

    public function process()
    {
        dump(1111111);
        /*set_time_limit(0);
        $forkNums = 20; //开启的进程数
        if (!function_exists("pcntl_fork")) {
            die("pcntl extention is must !");
        }

        for ($i=0;$i<$forkNums;$i++) {
            $pid = pcntl_fork();    //创建子进程
            if ($pid == -1) {
                //错误处理：创建子进程失败时返回-1.
                die('could not fork');
            } elseif ($pid) {
                pcntl_wait($status,WNOHANG);
                dump(11111);
            } else {
                $list = [123, 456, 789];
                foreach($list as $key=>$value){
                    //这里调用第三方接口，该过程大概需要3s
                    dump($value);
                    // ...  这里再对获取到的卡号信息进行自己相关的业务处理
                }
                unset($list);
                exit(0);
            }
        }*/
    }
}
