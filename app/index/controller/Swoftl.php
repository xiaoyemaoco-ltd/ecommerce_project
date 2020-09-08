<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/31
 * Time: 14:10
 */
namespace app\index\controller;
use app\BaseController;
use think\View;

class Swoftl extends BaseController{

    public function index(){
        return $this->fetch();
    }

    public function process()
    {
        dump(11111);
        set_time_limit(0);
        $forkNums = 20; //开启的进程数
//        if (!function_exists("pcntl_fork")) {
//            die("pcntl extention is must !");
//        }

        for ($i=0;$i<$forkNums;$i++) {
            $pid = pcntl_fork();    //创建子进程
            dump($pid);
        }
    }
}
