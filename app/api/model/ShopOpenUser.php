<?php
/**
 * Created by PhpStorm.
 * User: LENOVO
 * Date: 2020/8/1
 * Time: 19:38
 */
namespace app\api\model;
use think\Model;
class ShopOpenUser extends Model{
    //查询店铺信息
    public function selectall(){
        $rstult = ShopOpenUser::select();
        return $rstult;
    }
    //查询单个店铺信息
    public function selectone($owner_name){
        $rstult = ShopOpenUser::where($owner_name)->find();
        return $rstult;
    }

    //新增店铺
    public function add($data){
        $rstult = ShopOpenUser::save($data);
        return $rstult;
    }
}