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
        $type =  $request->post('type') ?? 'taobao';
//         dump(unserialize(Redis::get($this->uid.$type)));die;
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
                $arraydata =  TaoBao::taobaoserch($keyword,$page,$start_price,$end_price,$sort);
                Redis::set($this->uid.$type,serialize($arraydata),86400);
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



}
