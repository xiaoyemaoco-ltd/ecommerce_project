<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/19
 * Time: 9:53
 */
namespace app\admin\controller\command;
use app\common\controller\Backend;
class Sql extends Backend{
    public function initialize(){
        echo 1111111111;exit;
        parent::initialize();
    }
    public function index(){
        return $this -> fetch();
    }

}