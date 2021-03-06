<?php
namespace app\api\controller;
use DsfApi\PinDuoDuoOpen;
use think\App;
use think\facade\Db;
use think\facade\Session;
use app\Request;
class Pddopen extends Api {
   private $pinDuoDuo;
   public function initialize(){
       parent::initialize(); // TODO: Change the autogenerated stub
       $pddauth = Db::name('business_application_platform') -> where('name','pdd')  -> field('appkey,appsecret,redirect_uri') -> find();
       $config =[
           'clientId' => $pddauth['appkey'],
           'clientSecret' => $pddauth['appsecret'],
           'memberType' => 'MERCHANT',
           'redirectUrl' =>  $pddauth['redirect_uri'],
       ];
       $this -> pinDuoDuo = new PinDuoDuoOpen($config);
   }

   //点击跳转到授权页面
    public function pddbutton(){
        $userid = $this -> uid ?? 1;//用户ID
        $url = $this -> pinDuoDuo -> authorizationRedirect($userid);
        return $this -> success('200','',['url' => $url]);
//       //拼多多获取商品列表
//       $pinDuoDuo = \Yii::$app->pinDuoDuoOpen;
//       $pinDuoDuoGoodsData = $pinDuoDuo->request(PinDuoDuoOpen::API_PDD_GOODS_LIST_GET, ['page' => $this->page, 'page_size' => $this->page_size], ‘用户的access_token’);
   }
   //授权登录返回接口
   public function pddcallback(){
       $tokenData = $this -> pinDuoDuo->getAccessToken();

       dump($tokenData);
   }


   public function authkkk(){
       $pinDuoDuoGoodsData = $this -> pinDuoDuo ->request('pdd.goods.list.get',
           ['page' => $this->page, 'page_size' => $this->page_size], ‘用户的access_token’);
       //无需授权
       /** @var string 获取购买应用订单列表 */
//       const API_PDD_VAS_ORDER_SEARCH = 'pdd.vas.order.search';
//       //需授权
//       /** @var string 获取商品列表 */
//       const API_PDD_GOODS_LIST_GET = 'pdd.goods.list.get';
//       /** @var string 获取商品列表 */
//       const API_PDD_GOODS_DETAIL_GET = 'pdd.goods.detail.get';
//       /** @var string 修改商品信息 */
//       const API_PDD_GOODS_INFORMATION_UPDATE = 'pdd.goods.information.update';
//       /** @var string 上传图片 */
//       const API_PDD_GOODS_IMAGE_UPLOAD = 'pdd.goods.image.upload';
//       /** @var string 商品编辑结果查询 */
//       const API_PDD_GOODS_COMMIT_DETAIL_GET = 'pdd.goods.commit.detail.get';
//       /** @var string 获取店铺信息 */
//       const API_PDD_MALL_INFO_GET = 'pdd.mall.info.get';
   }

}
