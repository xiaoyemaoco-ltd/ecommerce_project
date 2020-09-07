<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/7/11
 * Time: 16:23
 */
namespace app\api\controller;
use think\facade\Db;
use think\Request;
class Member extends MemberController{

    //充值等级列表
    public function topuplevel(){
        $weblist = Db::name('topup_level') -> field('id,typename as title,money,dzmoney') -> where('status',1) -> order('sort asc') -> select();
        if(empty($weblist)){
            return $this -> error();
        }
        return $this -> success(200,'获取成功',$weblist);
    }
    public function caiji(){

        echo 1111111;
    }
}