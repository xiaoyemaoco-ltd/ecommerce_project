<?php
namespace app\api\controller;
use think\facade\Db;
use think\facade\Session;
use think\Request;
use think\facade\Cache;
class Gfilter extends Api {
    protected $noNeedLogin = ['index', 'mobilelogin', 'register','forget_pass', 'resetpwd', 'changeemail', 'changemobile', 'third'];
    public function index(){
        echo 1111;exit;
    }
}