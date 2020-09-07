<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/19
 * Time: 9:53
 */
namespace app\admin\controller\command;
use app\common\controller\Backend;
class Command extends Backend{
    public function initialize(){
        parent::initialize();
    }
    public function index(){
        if ($this->request->isAjax()) {
            $params = $this->request->post();
            if(empty($params)){
                $this->error('不能为空！');
            }
            epre($params);
            echo 111111111;exit;
        }
        return $this -> fetch();
    }
}