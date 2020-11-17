<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/20
 * Time: 14:52
 */
namespace app\api\controller;
use think\facade\Db;
use think\facade\Session;
use think\Request;
use think\facade\Cache;
use fast\Redis;
use DsfApi\TaoBao;
class Collection extends Api{
    protected $noNeedLogin = ['filter','test','goodslist'];
    protected $noNeedToken = ['collection_download','goodslist'];
    protected $pathfile = null;//保存的物理路径
    protected $path = null;//根路径
    public function initialize(){
        parent::initialize();
//        $this -> uid = $this -> uid ?? 1;
        $this -> path = "/collection";
        $this -> pathfile = date('Y年m月d日 H时i分s秒').'采集自动保存.txt';
    }
    /* 参数说明：
        * q:搜索关键字
        * cat:分类ID
        * start_price:开始价格
        * end_price:结束价格
        * sort:排序[bid,_bid,bid2,_bid2,_sale,_credit]
        * (bid:总价,bid2:商品价格,sale:销量,credit信用,加_前缀为从大到小排序)
        * page:页数
    * */
    public function goodslist(Request $request){
        $type =  $request->post('type') ?? 'taobao';
//         dump(unserialize(Redis::get($this->uid.$type)));die;
        $keyword = $request->post('keyword');
        if(!$keyword){
           return  $this->error('401','关键词不能为空');
        }
        $page =  $request->post('page') ??  1;
        $start_price = $request->post('start_price') ?? '';
        $end_price = $request->post('end_price') ?? '';
        $arraydata = [];
        switch ($type){
            case 'taobao':
                $sort = 'sale';
                $arraydata =  TaoBao::taobaoserch($keyword,$page,floatval($start_price),floatval($end_price),$sort);
                Redis::set($type.$this->uid,serialize($arraydata),18000);
                Redis::set($type.'_1688',serialize($arraydata),7200);
                break;
            case '1688';
                break;
        }
        //保存路径
//        if(!is_dir(SHUJUCUNCHU.$this -> path)){
//            mkdir(SHUJUCUNCHU.$this -> path,0777,true);
//        }
//        $file = SHUJUCUNCHU . $this -> path."/". $this -> pathfile;// 写入的文件
//        $myfile = fopen($file, "w") or die("Unable to open file!");
//        foreach ($arraydata as $k=>$v){
//            $content = $v['detail_url']."\r\n" ; // 写入的zhi内容
////                file_put_contents($file,$content,FILE_APPEND); // 最简单的快速的以追加的方式写入写入方法，
//            fwrite($myfile, $content);
//        }
//        fclose($myfile);
        $data = [
//            'path' => $this -> pathfile,
            'data' => $arraydata,
        ];
        return $this->success('200','获取成功',$data);
    }
    //自动下载
    public function collection_download(Request $request){
        $pathfile =  $request->get('pathfile');
        if(empty($pathfile)){
            return $this->error('401','文件路径不能为空!');
        }
        downloadFile($this -> path.'/'.$pathfile);
    }

    //产品信息列表
    public function getgoodlist(Request $request){
        $type = $request -> get('type');
        if(!$type){
            return $this->error('401','请选择所采集的对应平台!');
        }
        $data = unserialize(Redis::get($type.$this->uid));
//        $data = array_slice($data,14,1);
        return $this->success('200','获取成功',$data);
    }


    public function test($goodsid){

        $res = Cache::get('taobao'.$goodsid);
        dump($res);die;
    }

    /*
     * 过滤商品
     * $filtertype  过滤组 0 人工过滤
     *
     *
     *
    */
    public function filter(Request $request){
        $goodids = $request -> get('goodids');
//        dump(Redis::get($goodids.'_productdetail_title'));die;
        $data['title'] = Redis::get($goodids.'_title');
        $data['zhutus'] = unserialize(Redis::get($goodids.'_product_images_url'));
        $data['descimg'] = unserialize(Redis::get($goodids.'_product_descImg'));
        return $this->success('200','获取成功',$data);
//        $filtertype = $request -> get('filtertype');
//        $goodsid = $request-> get('goodsids');
//        switch ($filtertype){
//            case 1:
//                if(Cache::get("$goodsid")){}
//                $res = $this -> gooddelties($goodsid);
//                $row = serialize($res);
//                Cache::set("$goodsid",$row);
//                dump($res);
//                break;
//        }
    }

    public function goodeditsave(Request $request){
        $goodsid = $request->post('goodid');
        $title = $request->post('title');
        $goodeditzhutuvalue = $request->post('goodeditzhutuvalue');
        $goodeditdelvalue = $request->post('goodeditdelvalue');
//        if(is_arary($goodeditzhutuvalue)){
        $goodeditzhu = explode(',',$goodeditzhutuvalue);
        $goodeditdel = explode(',',$goodeditdelvalue);
//        }

//        Redis::set($goodsid.'_title',$title, 10);
        Redis::set($goodsid.'_title',$title, 10000);
        Redis::set($goodsid.'_product_images_url', serialize($goodeditzhu), 604800);
        Redis::set($goodsid.'_product_descImg', serialize($goodeditdel), 604800);
//        dump(Redis::get($goodsid.'_productdetail_title'));die;
        return $this -> success('200','保存成功',[]);
    }

    //获取商品详情
    public function one_details(Request $request){
        $strnumids = $request->post('goodsids');
        if (empty($strnumids)) {
            return $this -> error('401', 'ID组不能为空!');
        }
        $goodsid = explode(',', $strnumids);
        $a = array_shift($goodsid);
        do{
            //sleep(1);
            $res = $this -> gooddelties($a);
            $row = serialize($res);
            $array[$a] = $row;
//            $this->success('2','获取详情成功');
        }while($a = array_shift($goodsid));
        Cache::set("$strnumids",$array);
        return  $this->success('2','获取详情成功');
    }

}
