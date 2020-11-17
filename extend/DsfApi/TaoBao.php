<?php
/**
 * Created by PhpStorm.
 * User: LENOVO
 * Date: 2020/10/9
 * Time: 23:09
 */
namespace DsfApi;
class TaoBao{
    //获取商品类目
    public static function goodcate($goodsid){
        $obapi = self::openkey();
        $api_cat = $obapi->exec(
            array(
                "api_type" => "taobao",
                "api_name" => "item_cat_get",
                "api_params" => array(
                    'num_iid' => $goodsid,
                ))
        );
        if(empty($api_cat['item']['cat_name'])){
            return false;
        }
        $tbaodetile['root_cat_name'] = $api_cat['item']['root_cat_name'];//父级分类名称
        $tbaodetile['cat_name'] = $api_cat['item']['cat_name'];//分类名称
        return $tbaodetile;
    }
    //获取商品详情
    public static function goodsinfo($goodsid){
        $obapi = self::openkey();
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
        if($taoabo_detile['error'] == "item-not-found" ){
            return false;
        }
        $tbaodetile['cid'] = $taoabo_detile['item']['cid'];// 分类id
        $tbaodetile['rootCatId'] = $taoabo_detile['item']['rootCatId'];//顶级分类ID
        $tbaodetile['goodsid'] = $taoabo_detile['item']['num_iid'];//商品id
        $tbaodetile['title'] = $taoabo_detile['item']['title'];//商品id
        $tbaodetile['price'] = $taoabo_detile['item']['price'];//价格
        $tbaodetile['orginal_price'] = $taoabo_detile['item']['orginal_price'];//原价
        $tbaodetile['nick'] = $taoabo_detile['item']['nick'];//掌柜昵称
        $tbaodetile['num'] = $taoabo_detile['item']['num'];//库存
        $tbaodetile['min_num'] = $taoabo_detile['item']['min_num'];//最小购买数
        $tbaodetile['pic_url'] = $taoabo_detile['item']['pic_url'];//图片链接
        $tbaodetile['detail_url'] = $taoabo_detile['item']['detail_url'];
        $tbaodetile['brand'] = $taoabo_detile['item']['brand'];//品牌名称
        $tbaodetile['brandId'] = $taoabo_detile['item']['brandId'];//品牌ID
        $tbaodetile['desc'] = $taoabo_detile['item']['desc'];
        $tbaodetile['desc_short'] = $taoabo_detile['item']['desc_short'];//商品简介
        $tbaodetile['desc_img'] = $taoabo_detile['item']['desc_img'];//商品详情图
        $tbaodetile['item_imgs'] = array_column($taoabo_detile['item']['item_imgs'], 'url') ;//轮播图
        $tbaodetile['item_weight'] = $taoabo_detile['item']['item_weight'];
        $tbaodetile['location'] = $taoabo_detile['item']['location'];//发货地
        $tbaodetile['post_fee'] = $taoabo_detile['item']['post_fee'];//物流费用
        $tbaodetile['express_fee'] = $taoabo_detile['item']['express_fee'];//快递费用
        $tbaodetile['ems_fee'] = $taoabo_detile['item']['ems_fee'];//EMS费用
        $tbaodetile['shipping_to'] = $taoabo_detile['item']['shipping_to'];
        $tbaodetile['has_discount'] = $taoabo_detile['item']['has_discount'];
        $tbaodetile['video'] = $taoabo_detile['item']['video'];//商品视频
        $tbaodetile['is_promotion'] = $taoabo_detile['item']['is_promotion'];
        $tbaodetile['props_name'] = $taoabo_detile['item']['props_name'];//商品属性名
        $tbaodetile['prop_imgs'] = $taoabo_detile['item']['prop_imgs']['prop_img'];//商品属性图片列表
        $tbaodetile['props'] = $taoabo_detile['item']['props'];//商品详情
        $tbaodetile['total_sold'] = $taoabo_detile['item']['total_sold'];
        $tbaodetile['skus_list'] = $taoabo_detile['item']['skus']['sku'];
        $tbaodetile['seller_id'] = $taoabo_detile['item']['seller_id'];
        $tbaodetile['sales'] = $taoabo_detile['item']['sales'];
        $tbaodetile['props_list'] = $taoabo_detile['item']['props_list'];
        $tbaodetile['tmall'] = $taoabo_detile['item']['tmall'];
        $tbaodetile['shopinfo'] = $taoabo_detile['item']['shopinfo'];
        $tbaodetile['props_img'] = $taoabo_detile['item']['props_img'];//属性图片
        $tbaodetile['shopinfo'] = $taoabo_detile['item']['shopinfo'];
        $tbaodetile['shop_id'] = $taoabo_detile['item']['shop_id'];
        $tbaodetile['seller_info'] = $taoabo_detile['item']['seller_info'];
        return $tbaodetile;
    }

    //获取运费信息
    public function yunfei($goodsid,$area_id){
        $obapi = self::openkey();
        $taoabo_yunfei = $obapi->exec(
            array(
                "api_type" => "taobao",
                "api_name" => "item_fee",
                "api_params" => array(
                    'num_iid' => $goodsid,
                    'area_id' => $area_id,
                    'sku' => '0',
                )
            )
        );
        dump($taoabo_yunfei);die;
        $yunfei['num_iid'] = $taoabo_yunfei[''];
    }
    /**
     * 关键字搜索商品
     * @param string $remote_file_url
     * @param string $local_file
     */
    public static function taobaoserch($keyword,$page,$start_price,$end_price,$sort){
        $obapi = self::openkey();
        $api_data = $obapi->exec(
                array(
                    "api_type" =>"taobao",
                    "api_name" =>"item_search",
                    "api_params"=>array (
                        'q' => $keyword,
                        'start_price' => $start_price,
                        'end_price' => $end_price,
                        'page' => $page,
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
        if($api_data['error_code'] == '4013'){
            exit(json_decode($api_data['error']));
        }
        $arr = $api_data['items']['item'];
        $arraydata = array();
        if(count($arr) == 0){
            return $arraydata;
        }
        for ($i = 0; $i<count($arr);$i++){
            $arraydata[$i]['number'] = $i + 1;
            $arraydata[$i]['goodsid'] = $arr[$i]['num_iid'];
            $arraydata[$i]['title'] = $arr[$i]['title'];
            // $url = explode('&',  $arr[$i]['detail_url']);
            $arraydata[$i]['detail_url'] = $arr[$i]['detail_url'];
            $arraydata[$i]['price'] = $arr[$i]['price'];
            $arraydata[$i]['sales'] = $arr[$i]['sales'];
            $arraydata[$i]['post_fee'] = $arr[$i]['post_fee'];
            $arraydata[$i]['shop_nick'] = $arr[$i]['seller_nick'];
            $arraydata[$i]['area'] = $arr[$i]['area'];
            $arraydata[$i]['pic_url'] = '';
        }
        return $arraydata;
    }




    protected static function openkey(){
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
