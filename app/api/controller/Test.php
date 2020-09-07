<?php

namespace app\api\controller;

use app\BaseController;
use app\Request;
use think\facade\Cache;

class Test extends BaseController
{
    public function index(Request $request)
    {
        $post = $request->post();
        $arr = explode(',', $post['goodsids']);
        $a = array_shift($arr);
        $array = [];
//        $obapi = $this -> openkey();
        do{
            //sleep(1); // 按设置的时间等待一小时循环执行
            //$data = $this->gooddelties($a);
            /*$taoabo_detile = $obapi->exec(
                array(
                    "api_type" => "taobao",
                    "api_name" => "item_get",
                    "api_params" => array(
                        'num_iid' => $a,
                        'is_promotion' => '1',
                    )
                )
            );
            $api_cat = $obapi->exec(
                array(
                    "api_type" => "taobao",
                    "api_name" => "item_cat_get",
                    "api_params" => array(
                        'num_iid' => $a,
                    ))
            );
            dump($taoabo_detile);
            dump($api_cat);*/
            $res = $this->gooddelties($a);
            $row = serialize($res);
            $array[] = $row;
        }while($a = array_shift($arr));
        Cache::set('aaaaaaaaaaa', $array);
        return json_encode(['code' => 200, 'msg' => '采集成功', 'status' => 'ok']);
//        while (array_shift($arr)) {
//            echo array_shift($arr);
//        }
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
    
}
