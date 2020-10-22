<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/29
 * Time: 15:02
 */
namespace app\api\controller;
use app\Request;
use think\facade\Db;
use think\facade\Session;
/*
 * 店铺管理
 * */
class Thestore extends Api {
    public function initialize(){
        parent::initialize(); // TODO: Change the autogenerated stub
    }

    //获取当前用户下的所有店铺
    public function shops(Request $request){
        $res = Db::name('shop_open_user')
            -> where('uid',$this -> uid)
            -> field('id,shop_type,mall_name,logo,owner_name,mall_desc,endtime,login_time,is_token')
            -> order('endtime desc')
            -> select();
        return $this -> success('200','获取成功',$res);
    }

    //获取拼多多店铺
    public function pddshops(Request $request){
        $res = Db::name('shop_open_user')
            -> where(['shop_type'=>'pdd'])//'uid'=> $this-> uid,
            -> field('mall_name,owner_id,access_token,owner_name')
            -> select();
        return  $this -> success('200','获取成功',$res);
    }

    //删除当前用户下的店铺
    public function uiddel(Request $request){
        $typedel = $request -> post('typedel');
        $ids =  $request -> post('ids');
        if($typedel == 1){
            $where = "id = $ids";
        }else if($typedel == 2){
            $where = "uid =".$this -> uid." and id in ($ids)";
        }else{
            $where = ['uid' ,$this -> uid];
        }
        $res = Db::name('shop_open_user') -> where($where) ->  delete();

    }


}
