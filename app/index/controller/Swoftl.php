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
}