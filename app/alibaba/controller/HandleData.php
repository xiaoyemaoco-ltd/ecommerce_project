<?php


namespace app\alibaba\controller;

use app\alibaba\controller\HttpClient;
use app\BaseController;
use app\Request;
use think\App;
use fast\Redis;

class HandleData extends BaseController
{
    protected $access_token;
    protected $redirect_uri;
    protected $client_id;
    protected $client_secret;
    protected $roout_url;

    public function __construct(App $app)
    {
        parent::__construct($app);
        $row = getplatformMsg('alibaba');
        $this->redirect_uri = $row->redirect_uri;
        $this->client_id = $row->appkey;
        $this->client_secret = $row->appsecret;
        $this->roout_url = 'https://gw.open.1688.com/openapi/param2/1/';
        $this->access_token = Redis::get('alibaba_access_token');
    }

    /**
     * 商品发布
     * @param Request $request
     * @return false|string
     */
    public function productAdd(Request $request)
    {
        if (!$request->isPost()) return return_value('fail', '请求方式错误', 10001);
        $id = $request->post('goodsIds');
//        dump($id);die;
        $data = $this->getDoodsDetail($id);
        $namespace = 'com.alibaba.product';
        $apiName = 'alibaba.product.add';
        $data['access_token'] = $this->access_token;
//        dump($data);die;
        $res = $this->getAPiData($data, $namespace, $apiName);
        dump($res);die;
        return $data;
    }

    public function getDoodsDetail($goodsId)
    {
        $obapi = $this->openkey();
        $goodsDetail = $obapi->exec([
            "api_type" => "taobao",
            "api_name" => "item_get",
            'api_params' => [
                'num_iid' => $goodsId,
                'is_promotion' => true
            ]
        ]);

        if($goodsDetail['error'] == "item-not-found"){
            return false;
        }
//        dump($goodsDetail);die;
        $api_cat = $obapi->exec(
            array(
                "api_type" => "taobao",
                "api_name" => "item_cat_get",
                "api_params" => array(
                    'num_iid' => $goodsId,
                ))
        );

        if(empty($api_cat['item']['cat_name'])){
            return false;
        }
        //关键字搜索类目
        $cate = $this->getCategoryByKeyword($api_cat['item']['cat_name']);
        //获取最相近的类目
        $cateId = closest_word($api_cat['item']['cat_name'], $cate['products']);
        //获取类目属性
        $cateAttr = $this->getCateAttr($cateId);
//        dump($cateAttr);
        //alibaba商品属性
        $attr = $this->handleGoodsAttr($goodsDetail['item']['props'], $cateAttr['attributes']);
//        dump($attr);die;
        $skuAttr = $this->handleSkuAttr($goodsDetail['item']['props_list'], $cateAttr['attributes']);
//        dump($skuAttr);die;
        $skuInfo = $this->handleSkuInfo($goodsDetail['item']['skus']['sku'], $skuAttr, $goodsDetail['item']['prop_imgs']['prop_img']);
//        dump($skuInfo);die;
        $shipInfo = $this->getFreightTemplateById(14185977);

        //上传图片到相册
        $images = array_column($goodsDetail['item']['item_imgs'], 'url');
        $img = [];
        foreach ($images as $v) {
            $img[] = 'https://cbu01.alicdn.com/' . $this->imageUploadAlbum('https:' . $v);
        }

        $data = [
            'productType' => 'wholesale',
            'categoryID' => $cateId,
            'attributes' => json_encode($attr),
            'subject' => $goodsDetail['item']['title'],//标题
            'language' => 'CHINESE',
            'webSite' => '1688',
            'description' => $goodsDetail['item']['desc'],
            'pictureAuth' => false,
            //商品图片
            'image' => json_encode(['images' => $img]),
            //skuInfos
            'skuInfos' => json_encode($skuInfo),
            //商品销售信息
            'saleInfo' => json_encode([
                //是否支持网上交易
                'supportOnlineTrade' => true,
                //是否支持混批
                'mixWholeSale' => true,
                //是否价格私密信息
                'priceAuth' => false,
                //可售数量
                'amountOnSale' => $goodsDetail['item']['num'],
                //计量单位
                'unit' => '件',
                //最小起订量
                'minOrderQuantity' => 3,
                //每批数量 默认为空或者非零值，该属性不为空时sellunit为必填
                'batchNumber' => null,
                //建议零售价
                'retailprice' => $goodsDetail['item']['price'],
                //税率
                'tax' => '',
                //售卖单位，如果为批量售卖
                'sellunit' => '',
                //普通报价
                'quoteType' => $goodsDetail['item']['orginal_price'],
                //分销基准价
                'consignPrice' => $goodsDetail['item']['price'],
                //区间价格
                'priceRanges' => [
                    'startQuantity' => 3,
                    'price' => $goodsDetail['item']['price']
                ]
            ]),
            'shippingInfo' => json_encode([
                'freightTemplateID' => $shipInfo['id'],
                'sendGoodsAddressText' => $shipInfo['addressCodeText'],
                'sendGoodsAddressId' => $shipInfo['fromAreaCode']
            ])
        ];
        return $data;
    }
    public function test()
    {
        /*dump(Redis::get('alibaba_access_token'));die;*/
//        $data = $this->getDoodsDetail(560555826384);
//        $data = $this->getFreightTemplateById(14185977);
//        $data = $this->getLeafCategory(0);
//        $data = $this->getCateAttr(0);
        $data = $this->productAdd(35264040276);
//        $data = $this->createAlbum();
//        $data = $this->getSku();
        dump($data);die;
    }

    public function getSku()
    {
        $goodsId = 560555826384;
        $skuId = '4420251182231';
        $obapi = $this->openkey();
        $skuInfo = $obapi->exec([
            "api_type" => "taobao",
            "api_name" => "item_sku",
            'api_params' => [
                'num_iid' => $goodsId,
                'sku_id' => $skuId,
                'is_promotion' => true
            ]
        ]);

        dump($skuInfo);die;
    }

    /**
     * 上传图片
     * @param $img
     * @return mixed
     */
    public function imageUploadAlbum($img)
    {
        /*$fp = fopen($img,"rb");
        if ($fp) {
            $gambar = fread($fp,filesize($img));
            fclose($fp);
        }*/
        $albumID = Redis::get($this->access_token . '_alibaba_album');
        if (!$albumID) {
            $albumID = $this->getAlbumId();
        }
        $imgRes = base64_encode(file_get_contents($img));
        $namespace = 'com.alibaba.product';
        $apiName = 'alibaba.photobank.photo.add';
        $data = [
            'albumID' => $albumID,
            'name' => 'image',
            'webSite' => '1688',
            'imageBytes' => $imgRes,
            'access_token' => $this->access_token
        ];
        $data = $this->getAPiData($data, $namespace, $apiName);
        if ($data['errorCode'] == 0) {
            return $data['image']['url'];
        } else {
            $this->getAlbumId();
            $url = $this->imageUploadAlbum($img);
            return $url;
        }
    }

    /**
     * 获取相册ID
     * @return mixed
     */
    public function getAlbumId()
    {
        $namespace = 'com.alibaba.product';
        $apiName = 'alibaba.photobank.album.getList';
        $data = [
            'webSite' => '1688',
            'access_token' => $this->access_token
        ];
        $data = $this->getAPiData($data, $namespace, $apiName);
        $albumId = '';
        foreach ($data['albumInfos'] as $v) {
            if ($v['imageCount'] < 500) {
                $albumId = $v['albumID'];
            }
        }
        Redis::set($this->access_token . '_alibaba_album', $albumId, 86400);
        return $albumId;
    }

    /**
     * 创建相册
     * @return array|mixed
     */
    protected function createAlbum()
    {
        $namespace = 'com.alibaba.product';
        $apiName = 'alibaba.photobank.album.getList';
        $data = [
            'name' => '相册',
            'authority' => 1,
            'webSite' => '1688',
            'access_token' => $this->access_token
        ];
        $data = $this->getAPiData($data, $namespace, $apiName);
        return $data;
    }

    /**
     * SKUinfo数据
     * @param $sku
     * @param $attr
     * @param $prop_img
     * @return array
     */
    public function handleSkuInfo($sku, $attr, $prop_img)
    {
        $propImg = [];
        foreach ($prop_img as $v) {
//            $imageUrl = 'https://cbu01.alicdn.com/' . $this->imageUploadAlbum('https:' . $v['url']);
            $propImg[] = $v['url'];
        }
        $propName = array_column($sku, 'properties_name');
        $propArr = [];
        foreach ($propName as $k => $v) {
            $propArr[] = $this->getSkuAttr($v, $attr);
        }
        $arr = [];
        $attributes = [];
        foreach ($sku as $k => $v) {
            $i = 0;
            foreach ($propArr[$k] as $key => $val) {
                $attributes[$i]['attributeID'] = $key;
                $attributes[$i]['attributeValue'] = $val;
                $attributes[$i]['skuImageUrl'] = $propImg[$k];
                $i++;
            }
            $arr[$k]['attributes'] = $attributes;
            $arr[$k]['price'] = $v['orginal_price'];
            $arr[$k]['retailPrice'] = $v['price'];
        }
        return $arr;
    }

    /**
     * sku属性
     * @param $prop_name
     * @param $attr
     * @return array
     */
    public function getSkuAttr($prop_name, $attr)
    {
        $propArr = explode(';', $prop_name);
        $arr = [];
        foreach ($propArr as $k => $v) {
            list($a, $b, $c, $d) = explode(':', $v);
//            $arr[] = $c;
            $arr[] = $d;
        }
        $array = [];
        foreach ($arr as $key => $val) {
            foreach ($attr as $k => $v) {
                if ($v['value'] == $val) {
                    $array[$v['attributeID']] = $val;
                }
            }
        }
        return $array;
    }

    /**
     * 处理sku所需的属性
     * @param $prop
     * @param $attributes
     * @return array
     */
    public function handleSkuAttr($prop, $attributes)
    {
        $array = [];
        foreach ($prop as $k => $v) {
            list($a, $b) = explode(':', $v);
            $array[$k][0] = $a;
            $array[$k][1] = $b;
        }
        $attr = [];
        $i = 0;
        foreach ($array as $key => $val) {
            foreach ($attributes as $k => $v) {
                if (strpos($val[0], $v['name'])!==false) {
                    $attr[$i]['attributeID'] = $v['attrID'];
                    $attr[$i]['attributeName'] = $v['name'];
                    $attr[$i]['value'] = $val[1];
                    $attr[$i]['isCustom'] = false;
                    $i++;
                }
            }
        }
        return $attr;
    }
    /**
     *  处理商品属性
     * @param $prop
     * @param $attributes
     * @return array
     */
    public function handleGoodsAttr($prop, $attributes)
    {
        //取出必须属性
        $requireAttr = [];
        foreach ($attributes as $k => $v) {
            if ($v['required']) {
                $requireAttr[$v['attrID']]['attributeID'] = $v['attrID'];
                $requireAttr[$v['attrID']]['attributeName'] = $v['name'];
                $requireAttr[$v['attrID']]['isCustom'] = false;
                $requireAttr[$v['attrID']]['value'] = 1;
            }
        }
        //获取有值的属性
        $attr = [];
        foreach ($prop as $key => $val) {
            foreach ($attributes as $k => $v) {
                if (strpos($val['name'], $v['name']) !== false) {
                    $attr[$v['attrID']]['attributeID'] = $v['attrID'];
                    $attr[$v['attrID']]['attributeName'] = $v['name'];
                    $attr[$v['attrID']]['isCustom'] = false;
//                    $attr[$v['attrID']]['value'] = $val['value'];
                    $attr[$v['attrID']]['value'] = strlen($val['value']) > 50 ? '' : mb_substr($val['value'], 0, 50);
                }
            }
        }
        $arr = array_values($attr + $requireAttr);
        return $arr;
    }

    /**
     * 处理关键字类目
     * @param $cate
     * @param $catName
     * @return string
     */
    public function handleCate($cate, $catName)
    {
        $categoryId = '';
        foreach ($cate['products'] as $k => $v) {
            if (!Redis::get('alibaba_leaf_category_' . $v['name'])) {
                Redis::set('alibaba_leaf_category_' . $v['name'], $v['categoryID'], 2592000);
            }
            similar_text($v['name'], $catName, $percent);
            if ($percent >= 50 && $v['isLeaf']) {
                $categoryId = $v['categoryID'];
            }
        }
        return $categoryId;
    }

    public function getFreightTemplateById($id)
    {
        $namespace = 'com.alibaba.logistics';
        $apiName = 'alibaba.logistics.myFreightTemplate.list.get';
        $data = [
            'templateId' => $id,
            'webSite' => '1688',
            'access_token' => $this->access_token
        ];
        $data = $this->getAPiData($data, $namespace, $apiName);
        return $data['result'][0];
    }

    /**
     * 获取运费模板列表
     * @return array|mixed
     */
    public function getFreightTemplate()
    {
        $namespace = 'com.alibaba.logistics';
        $apiName = 'alibaba.logistics.freightTemplate.getList';
        $data = [
            'webSite' => '1688',
            'access_token' => $this->access_token
        ];
        $data = $this->getAPiData($data, $namespace, $apiName);
        return return_value('ok', '获取成功', $data, 10000);
    }

    /**
     * 获取商品列表
     * @return array|mixed
     */
    public function getProduct()
    {
        $namespace = 'com.alibaba.product';
        $apiName = 'alibaba.product.get';
        $data = [
            'productID' => 627323016937,
            'webSite' => '1688',
            'access_token' => $this->access_token
        ];
        $data = $this->getAPiData($data, $namespace, $apiName);
        dump($data);die;
        return $data;
    }

    /**
     * 获取类目属性
     * @param $cateId
     * @return array|mixed
     */
    public function getCateAttr($cateId)
    {
//        $cateId = 1033258;
        $namespace = 'com.alibaba.product';
        $apiName = 'alibaba.category.attribute.get';
        $data = [
            'categoryID' => $cateId,
            'webSite' => '1688',
            'access_token' => $this->access_token
        ];
        $data = $this->getAPiData($data, $namespace, $apiName);
        return $data;
    }

    /**
     * 获取类目
     * @param $cateId
     * @return array|mixed
     */
    public function getLeafCategory()
    {
        $cateId = 3216;
        $namespace = 'com.alibaba.product';
        $apiName = 'alibaba.category.get';
        $data = [
            'categoryID' => $cateId
        ];
        $data = $this->getAPiData($data, $namespace, $apiName);
//        dump($data);die;
//        $rootCate = $data['categoryInfo'][0];
        foreach ($data['categoryInfo'][0]['childCategorys'] as $key => $val) {
            if (!$data['categoryInfo'][0]['isLeaf']) {
                if (!Redis::get($val['name'])) {
                    Redis::set($val['name'], $val['id'], 2592000);
                    $array = $this->getLeafCategory($val['id'], $array);
                }
                //dump($array);
            } else {
                if (!Redis::get($data['categoryInfo'][0]['name'])) {
                    Redis::set($data['categoryInfo'][0]['name'], $rootCate['categoryID'], 2592000);
                }
            }
        }
        return $array;
    }

    /**
     * 根据关键字获取类目
     * @param $keyword
     * @return array|mixed
     */
    public function getCategoryByKeyword($keyword)
    {
//        $keyword = '无线鼠标';
        $namespace = 'com.alibaba.product';
        $apiName = 'alibaba.category.searchByKeyword';
        $data = [
            'keyword' => $keyword,
            'access_token' => $this->access_token
        ];
        $data = $this->getAPiData($data, $namespace, $apiName);
        return $data;
    }

    protected function openkey()
    {
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

    /**
     * 获取根类目
     * @param Request $request
     */
    public function getRootCategory()
    {
        $namespace = 'com.alibaba.product';
        $apiName = 'alibaba.category.get';
        $data = [
            'categoryID' => 0
        ];
        $data = $this->getAPiData($data, $namespace, $apiName);
        $rootCate = $data['categoryInfo'][0];
        foreach ($rootCate['childCategorys'] as $k => $v) {
            if (!Redis::get('root_category_id_' . $rootCate['name'])) {
                Redis::set('root_category_id_' . $rootCate['name'], $v['id'], 2592000);
            }
        }
        dump($data);die;
        return $data;
    }

    /**
     * 签名
     * @param $data
     * @param $namespace
     * @param $apiName
     * @return string
     */
    public function alibabaSign ($data, $namespace, $apiName)
    {
        $aliParams = [];
        foreach ($data as $key => $val) {
            $aliParams[] = $key . $val;
        }
        sort($aliParams);
        $sign_str = join('', $aliParams);
        $sign_str = 'param2/1/' . $namespace . '/' . $apiName . '/' . $this->client_id . $sign_str;
        $code_sign = strtoupper(bin2hex(hash_hmac("sha1", $sign_str, $this->client_secret, true)));
        return $code_sign;
    }

    /**
     * 获取接口数据
     * @param $data
     * @param $namespace
     * @param $apiName
     * @return mixed
     */
    protected function getAPiData($data, $namespace, $apiName)
    {
        $arr = $data;
        $arr['_aop_signature'] = $this->alibabaSign($data,$namespace , $apiName);
        $url = $this->roout_url . $namespace . '/' . $apiName . '/' . $this->client_id;
        $bits = parse_url($url);
        $host = $bits['host'];
        $port = isset($bits['port']) ? $bits['port'] : 80;
        $path = isset($bits['path']) ? $bits['path'] : '/';
        $httpClient = new HttpClient($host, $port);
        $data = $httpClient->quickPost($url, $arr);
        return json_decode($data, 1);
    }
}
