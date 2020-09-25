<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/17
 * Time: 14:54
 */
namespace app\api\controller;
use think\facade\Db;
use think\Request;
use fast\Image;
class Index extends Api{
    protected $noNeedLogin = ['index'];
    public function initialize(){
        return parent::initialize(); // TODO: Change the autogenerated stub
    }

    public function index(){
        $img = "http://img.alicdn.com/imgextra/i4/2996327771/O1CN01oV5DYk27HAQBklvBi_!!2996327771.jpg";
        $img = imgtobase64($img,1,750,320);
        echo $img;die;
////        $_path = 'D:/test1.png';
//
//        $_img = new Image(app()->getRootPath().$img);//$_path为图片文件的路径
//        $_img->thumb(150, 100);
//        $_img->out();
    }

    //网站列表
    public function weblist(Request $request){
        //查询网站目录
        $weblist = Db::name('business_platform')
            -> field('name,title,electricity_url as weburl')
            -> where('status','normal')
            -> order('sort asc')
            -> select() -> toArray();
        return  $this->success('200','获取成功',$weblist);
    }

    //店铺授权登录
    public function pshoplogin(Request $request){
        $plogin = Db::name('business_application_platform')
            -> field('id,name,title,appkey,redirect_uri,loginurl,istype')
            -> where('status','normal')
            -> order('sort asc')
            -> limit(0,5)
            -> select() -> all();
        $data = [];
        foreach ($plogin as $key => $val){
            $data[$key]['name'] = $val['name'];
            $data[$key]['title'] = $val['title'];
            $data[$key]['loginurl']  = $this -> getHref($val['name'],$val['appkey'],$val['redirect_uri'],$val['loginurl']);
        }
        return $this->success('200','获取成功',$data);
    }

    //店铺登录
    public function shoplogin(Request $request){
        $plogin = Db::name('business_application_platform')
            -> field('id,name,title,appkey,redirect_uri,loginurl,istype,appsecret')
            -> where(['status'=>'normal','istype'=>0])
            -> order('sort asc')
            -> limit(0,5)
            -> select() -> all();
        return  $this->success('200','获取成功',$plogin);
    }

    //生成登录链接
    protected function getHref($type,$appkey,$backurl,$loginurl){
        $query = 'response_type=code&client_id='.$appkey. '&redirect_uri=' .urlencode($backurl) . '&state=1212';
        $url = '';
        switch ($type){
            case 'alibaba':
                $url = $loginurl . '?client_id=' . $appkey .'&site=1688&redirect_uri='. urlencode($backurl) .'&state=YOUR_PARM';
                break;
            case 'taobao':
                $url = $loginurl;
                break;
            case 'pdd':
                if (is_mobile() != true) {
                    $url = 'https://mms.pinduoduo.com/open.html?' . $query; // pc端
                } else {
                    $url = 'https://mai.pinduoduo.com/h5-login.html?' . $query . '&view=h5'; // 手机端
                }
                break;
            case 'ddk':
                $url = 'https://jinbao.pinduoduo.com/open.html?' . $query; // 拼客客
                break;
        }
        return $url;
    }







}
