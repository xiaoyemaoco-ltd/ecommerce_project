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
//use think\Download;
class Collection extends Api{
    protected $noNeedLogin = ['filter'];
    protected $noNeedToken = ['collection_download'];
    protected $pathfile = null;//保存的物理路径
    protected $path = null;//根路径
    public function initialize(){
        parent::initialize();
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
        $type =   $request->post('type') ?? 'taobao';
        $keyword = $request->post('keyword');
        if(!$keyword){
           return  $this->error('401','关键词不能为空');
        }
        $page =  empty($request->post('page')) ?  1 : $request->post('page');
        $start_price = $request->post('start_price') ?? '';
        $end_price = $request->post('end_price') ?? '';
        $arraydata = [];
        switch ($type){
            case 'taobao':
                $sort = 'sale';
                $arraydata = $this -> taobao($keyword,$page,$start_price,$end_price,$sort);
                break;
            case '1688';
                break;
        }
        //保存路径
        if(!is_dir(SHUJUCUNCHU.$this -> path)){
            mkdir(SHUJUCUNCHU.$this -> path,0777,true);
        }
        $file = SHUJUCUNCHU . $this -> path."/". $this -> pathfile;// 写入的文件
        $myfile = fopen($file, "w") or die("Unable to open file!");
        foreach ($arraydata as $k=>$v){
            $content = $v['detail_url']."\r\n" ; // 写入的zhi内容
//                file_put_contents($file,$content,FILE_APPEND); // 最简单的快速的以追加的方式写入写入方法，
            fwrite($myfile, $content);
        }
        fclose($myfile);
        $data = [
            'path' => $this -> pathfile,
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



    /**
     * 把远程文件保存到本地
     * @param string $remote_file_url
     * @param string $local_file
     */

    protected function taobao($keyword,$page,$start_price,$end_price,$sort){
        $obapi = $this -> openkey();
        $arr = [];
        for($i=1;$i<=$page;$i++){
            $api_data = $obapi->exec(
                array(
                    "api_type" =>"taobao",
                    "api_name" =>"item_search",
                    "api_params"=>array (
                        'q' => $keyword,
                        'start_price' => $start_price,
                        'end_price' => $end_price,
                        'page' => $i,
                        'cat' => '0',
                        'discount_only' => '',
                        'sort' => $sort,
                        'page_size' => 100,
                        'seller_info' => '',
                        'nick' => '',
                        'ppath' => '',
                        'imgid' => '',
                        'filter' => '',
                    )
                )
            );
            $arr = array_merge($arr,$api_data['items']['item']);
        }

        $arraydata = array();
        if(count($arr) == 0){
            return $arraydata;
        }
        for ($i = 0; $i<count($arr);$i++){
            $arraydata[$i]['number'] = $i + 1;
            $arraydata[$i]['title'] = $arr[$i]['title'];
            $url = explode('&',  $arr[$i]['detail_url']);
            $arraydata[$i]['detail_url'] = $url[0];
            $arraydata[$i]['price'] = $arr[$i]['price'];
            $arraydata[$i]['sales'] = $arr[$i]['sales'];
            $arraydata[$i]['post_fee'] = $arr[$i]['post_fee'];
            $arraydata[$i]['shop_nick'] = $arr[$i]['seller_nick'];
            $arraydata[$i]['area'] = $arr[$i]['area'];
        }
        return $arraydata;
    }
    /*
     * 过滤商品
     * $filtertype  过滤组 0 人工过滤
     *
     *
     *
    */
    public function filter(Request $request){
        $filtertype = $request -> get('filtertype');
        $goodsid = $request-> get('goodsids');
        switch ($filtertype){
            case 1:
                if(Cache::get("$goodsid")){}
                $res = $this -> gooddelties($goodsid);
                $row = serialize($res);
                Cache::set("$goodsid",$row);
                dump($res);
                break;
        }
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

    //判断商品是否存在
    public function istaobaogoods($goodsid){
        $goodsdata = [];
        $info = Db::name('goodsinfo')->where(['goods_id' => $goodsid])->find();
        if(empty($info)){
            $taoabo_detile = $this -> gooddelties($goodsid);
            if(empty($taoabo_detile)) return;
            $goodsdata['goods_id'] = $taoabo_detile['num_iid'];
            $goodsdata['cat_id'] = $taoabo_detile['cid'];
            $goodsdata['cat_name'] = $taoabo_detile['cat_name'];//分类名称
            $goodsdata['root_cat_name'] = $taoabo_detile['root_cat_name'];//父级分类名称
            $goodsdata['root_cat_id'] = $taoabo_detile['rootCatId'];//顶级分类ID
            $goodsdata['good_detile'] = json_encode($taoabo_detile);//顶级分类ID
            $goodsdata['create_time'] =getday()['week'];
            Db::name('goodsinfo') -> insert($goodsdata);
        }else{
            if (time() - strtotime($info['create_time']) > 0) {
                $taoabo_detile = $this -> gooddelties($goodsid);
                if(empty($taoabo_detile)) return;
                $goodsdata['goods_id'] = $taoabo_detile['num_iid'];
                $goodsdata['cat_id'] = $taoabo_detile['cid'];
                $goodsdata['cat_name'] = $taoabo_detile['cat_name'];//分类名称
                $goodsdata['root_cat_name'] = $taoabo_detile['root_cat_name'];//父级分类名称
                $goodsdata['root_cat_id'] = $taoabo_detile['rootCatId'];//顶级分类ID
                $goodsdata['good_detile'] = json_encode($taoabo_detile);//顶级分类ID
                $goodsdata['create_time'] =getday()['week'];
                Db::name('goodsinfo')->where('goods_id', $goodsid)->update($goodsdata);
            }
            $arr['cat_id'] = $info['cat_id'];
            $arr['cat_name'] = $info['cat_name'];
            $arr['good_detile'] = json_decode($info['good_detile'],true);
        }
        if(!empty($goodsdata)){
            $arr['cat_id'] = $goodsdata['cat_id'];
            $arr['cat_name'] = $goodsdata['cat_name'];
            $arr['good_detile'] = json_decode($goodsdata['good_detile'],true);
        }
        return $arr;
    }
    protected function gooddelties($goodsid){
        $obapi = $this -> openkey();
        $taoabo_detile = $obapi->exec(
            array(
                "api_type" => "taobao",
                "api_name" => "item_get",
                "api_params" => array(
                    'num_iid' => $goodsid,
                    'is_promotion' => '1',
                )
            )
        );
        $api_cat = $obapi->exec(
            array(
                "api_type" => "taobao",
                "api_name" => "item_cat_get",
                "api_params" => array(
                    'num_iid' => $goodsid,
                ))
        );
        if($taoabo_detile['error'] == "item-not-found" || empty($api_cat['item']['cat_name'])){
            return false;
        }
        $tbaodetile['cid'] = $taoabo_detile['item']['cid'];// 分类id
        $tbaodetile['rootCatId'] = $taoabo_detile['item']['rootCatId'];//顶级分类ID
        $tbaodetile['root_cat_name'] = $api_cat['item']['root_cat_name'];//父级分类名称
        $tbaodetile['cat_name'] = $api_cat['item']['cat_name'];//分类名称
        $tbaodetile['num_iid'] = $taoabo_detile['item']['num_iid'];
        $tbaodetile['title'] = $taoabo_detile['item']['title'];
        $tbaodetile['price'] = $taoabo_detile['item']['price'];
        $tbaodetile['orginal_price'] = $taoabo_detile['item']['orginal_price'];
        $tbaodetile['nick'] = $taoabo_detile['item']['nick'];
        $tbaodetile['num'] = $taoabo_detile['item']['num'];
        $tbaodetile['min_num'] = $taoabo_detile['item']['min_num'];
        $tbaodetile['pic_url'] = $taoabo_detile['item']['pic_url'];
        $tbaodetile['detail_url'] = $taoabo_detile['item']['detail_url'];
        $tbaodetile['brand'] = $taoabo_detile['item']['brand'];//品牌名称
        $tbaodetile['brandId'] = $taoabo_detile['item']['brandId'];//品牌ID
        $tbaodetile['desc'] = $taoabo_detile['item']['desc'];
        $tbaodetile['desc_img'] = $taoabo_detile['item']['desc_img'];
        $tbaodetile['item_imgs'] = $taoabo_detile['item']['item_imgs'];
        $tbaodetile['item_weight'] = $taoabo_detile['item']['item_weight'];
        $tbaodetile['location'] = $taoabo_detile['item']['location'];
        $tbaodetile['post_fee'] = $taoabo_detile['item']['post_fee'];
        $tbaodetile['express_fee'] = $taoabo_detile['item']['express_fee'];
        $tbaodetile['ems_fee'] = $taoabo_detile['item']['ems_fee'];
        $tbaodetile['shipping_to'] = $taoabo_detile['item']['shipping_to'];
        $tbaodetile['has_discount'] = $taoabo_detile['item']['has_discount'];
        $tbaodetile['video'] = $taoabo_detile['item']['video'];
        $tbaodetile['is_promotion'] = $taoabo_detile['item']['is_promotion'];
        $tbaodetile['props_name'] = $taoabo_detile['item']['props_name'];
        $tbaodetile['prop_imgs'] = $taoabo_detile['item']['prop_imgs']['prop_img'];
        $tbaodetile['props'] = $taoabo_detile['item']['props'];
        $tbaodetile['total_sold'] = $taoabo_detile['item']['total_sold'];
        $tbaodetile['skus_list'] = $taoabo_detile['item']['skus']['sku'];
        $tbaodetile['seller_id'] = $taoabo_detile['item']['seller_id'];
        $tbaodetile['sales'] = $taoabo_detile['item']['sales'];
        $tbaodetile['props_list'] = $taoabo_detile['item']['props_list'];
        $tbaodetile['tmall'] = $taoabo_detile['item']['tmall'];
        $tbaodetile['shopinfo'] = $taoabo_detile['item']['shopinfo'];
        $tbaodetile['props_img'] = $taoabo_detile['item']['props_img'];
        $tbaodetile['shopinfo'] = $taoabo_detile['item']['shopinfo'];
        $tbaodetile['shop_id'] = $taoabo_detile['item']['shop_id'];
        $tbaodetile['seller_info'] = $taoabo_detile['item']['seller_info'];
        return $tbaodetile;
    }

    protected function openkey(){
        $method = "GET";
        // 请求示例 url 默认请求参数已经URL编码处理
        //定义缓存目录和引入文件
        define("DIR_RUNTIME","runtime/");
        define("DIR_ERROR","runtime/");
        define("SECACHE_SIZE","0");
        $key = "tel18202970012";
        $secret = "20200619";
        $obapi = new \otao\ObApiClient();
        $obapi->api_url = "http://api.onebound.cn/";
        $obapi->api_urls = array("http://api.onebound.cn/","http://api-1.onebound.cn/");//备用API服务器
        $obapi->api_urls_on = true;//当网络错误时，是否启用备用API服务器
        $obapi->api_key =$key;
        $obapi->api_secret = $secret;
        $obapi->api_version ="";
        $obapi->secache_path ="runtime/";
        $obapi->secache_time ="86400";
        $obapi->cache = true;
        return $obapi;
    }




//    public function onedelets(){
//        $file_path = SHUJUCUNCHU."/collection/2020年09月02日 09时44分58秒采集自动保存.txt";
//        if(file_exists($file_path)){
//            $str = file_get_contents($file_path);//将整个文件内容读入到一个字符串中
//            $str = str_replace("\r\n","|",$str);
//        }
//        $goodsarray = explode('|',$str);
//        array_pop($goodsarray);//抛出二维数组的最后一个数组
////        $a = explode('?id=',$goodsarray)[1];
//        $arr =[];
//        for($i=0;$i<count($goodsarray);$i++){
////            $arr[$i]['typename'] = explode('.',$goodsarray[$i])[1];
//            $arr[] = explode('?id=',$goodsarray[$i])[1];
//        }
//        do{
//            sleep(1);
//        }while($a = array_shift($arr));
////        epre($arr);exit;
////        foreach ($arr as $key => $val){
////            $this -> goodsinfo($val['goodsid']);
//////            echo $val['goodsid'].' &nbsp;采集信息成功 <br>';
////        }
//    }


//    public function goodsinfo($goodid){
//        $info = Db::name('goodsinfo')->where(['goods_id' => $goodid])->find();
//        if(empty($info)){
//            $taoabo_detile = $this -> gooddelties($goodid);
////            echo $goodid.' &nbsp;采集信息成功111 <br>';
//        }else{
//            echo $goodid.' &nbsp;采集信息成功 <br>';
//        }
//    }
    //一键获取商品详情
//    public function good_details1(){
//        $strnumids = $request->post('goodsids');
//        if (empty($strnumids)) {
//            return  $this->error('401','ID组不能为空!');
//        }
//
//        Redis::del('caijigoods');
////        $strnumids = "619058978586,620741461587,617656221079,525426774191,623098162501,619606295802";//
//        $arrdata = explode(',', $strnumids);
////        array_pop($arrdata);//抛出二维数组的最后一个数组
//        foreach ($arrdata as $k => $v) {
//            Redis::rpush('caijigoods', $v);
//            echo $v . "号入队成功" . "<br/>";
//        }
//        while (1) {
//            try {
//                $val = Redis::lpop('caijigoods');
//                if (!$val) {
//                    break;
//                }
//                $deletsdata[] = $this-> gooddelties($val);
//
//            } catch (\Exception $e) {
//                echo $e->getMessage();
//            }
//            epre($deletsdata) . "<br/>";
//
//            return $this->success('200','获取信息成功',$deletsdata);
//        }
//    }
}
