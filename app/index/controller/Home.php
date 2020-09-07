<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/19
 * Time: 17:54
 */
namespace app\index\controller;
use app\BaseController;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/5/7
 * Time: 11:30
 */
class Home  extends BaseController{
    public function initialize(){
        parent::initialize();
        /* 读取站点配置 */
        if(!C('web_close')){
            $this->error('站点已经关闭，请稍后访问~');
        }
        $this -> assign('nav_list',$this -> nav_list());
        $this -> assign('company_logo',$this -> company_logo());

    }

    public function company_logo(){
        if(!is_mobile()){
            $company_logo = C('web_logo');
        }else{
            $company_logo = C('mobile_logo');
        }
        return $company_logo;
    }

    public function nav_list(){
        $list = [
            '0'=> ['title' => '产品中心','url_href'=>'product/production','sort'=>1],
            '1'=> ['title' => '服务中心','url_href'=>'index/index','sort'=>2],
            '2'=> ['title' => '产品解决方案','url_href'=>'product/solution','sort'=>3],
            '3'=> ['title' => '关于我们','url_href'=>'index/about','sort'=>4]
        ];
        // 2.取'sort'列数据进行升序排列
        array_multisort(array_column($list, 'sort'),SORT_ASC,$list);
        return $list;
    }
}