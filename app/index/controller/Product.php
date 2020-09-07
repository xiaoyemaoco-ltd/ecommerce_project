<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/14
 * Time: 10:07
 */
namespace app\index\controller;
use think\facade\Db;
class Product extends Home{
    public function production(){
        $this -> assign('title',C('web_name').'-产品中心');
        return $this -> fetch();
    }
    //产品解决方案
    public function solution(){
        $this -> assign('title',C('web_name').'-产品解决方案');
        return $this -> fetch();
    }

    //产品解决方案详情
    public function detail(){
        $this -> assign('title',C('web_name').'-解决方案详情');
        return $this -> fetch();
    }
}