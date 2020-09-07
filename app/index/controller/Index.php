<?php
/**
 * Created by PhpStorm.
 * User: LENOVO
 * Date: 2020/8/3
 * Time: 19:49
 */
namespace app\index\controller;
use think\facade\Db;
use think\facade\View;

class Index extends Home{
    public function index(){
        //查询产品
//         $join1 = [['sxtyyd_image img','img.id=pr.pic_id']];
//         $procudt = Db::name('procudt') -> alias('pr') -> leftJoin($join1)
//             -> field('pr.id as prid,pr.procdut_name as title,pr.procdut_url,img.imgsrc,img.imgurl')
//             -> where(['status'=>1,'recommended'=>1]) -> select();
//          if(!empty($procudt)){
//              exit(successok($procudt,'获取信息成功！'));
//          }
        $this -> assign('title',C('web_name').'-首页');
        return $this -> fetch();
    }

    //关于我们
    public function about(){
        $this -> assign('title',C('web_name').'-关于我们');
        return $this -> fetch();
    }

    //查询产品信息
    public function productinfo(){
        $pid = $_GET['id'];
        $info = Db::name('procudt') -> where('id',$pid) -> find();

    }

    public function wuJing()
    {
        return $this->fetch();
    }
}
