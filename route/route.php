<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/21
 * Time: 10:54
 */
use think\facade\Route;
Route::group('api',function (){
    Route::post('sms/send','api/sms/send'); //发送验证码、
    /****************     会员   ************/
    Route::post('user/register','api/user/register') ; //用户注册接口
    Route::any('user/login','api/user/login')->allowCrossDomain(); //登录接口
    Route::any('user/forget_pass','api/user/forget_pass'); //忘记密码
    Route::any('user/outlogin','api/user/outlogin'); //退出登录
    /****************   会员   ************/

    Route::any('index/index', 'api/index/index');//获取网站
    Route::any('index/weblist', 'api/index/weblist');//获取网站
    Route::any('index/pshoplogin', 'api/index/pshoplogin');//获取网站

//    Route::get('user/topuplevel','api/index/topuplevel'); //获取充值等级类型
    /****************   采集商品或信息   ************/
    Route::post('collection/goodslist','api/collection/goodslist');//采集商品列表
    Route::post('collection/getgoodlist','api/collection/getgoodlist');//采集商品列表
    Route::any('collection/goodeditsave','api/collection/goodeditsave'); // 拼多多店铺授权
    Route::any('collection/filter','api/collection/filter'); // 拼多多店铺授权

    Route::any('collection/collection_download','api/collection/collection_download');//自动保存
    Route::any('collection/one_details','api/collection/one_details');//采集单个商品信息

    Route::any('collection/good_details','api/collection/good_details');//采集单个商品信息
    /****************   采集商品或信息   ************/

//    Route::get('collection/goodslist','api/collection/goodslist');
    /****************店铺管理************/
    Route::any('thestore/pddshops','api/thestore/pddshops'); // 获取拼多多店铺
    Route::post('thestore/shops','api/thestore/shops'); // 获取当前店铺管理
    /****************店铺管理************/

    /****************店铺登录************/
    Route::any('shopauth/pddbutton','api/shopauth/pddbutton'); //点击跳转到授权页面
    Route::any('shopauth/pddauthlogin','api/shopauth/pddauthlogin'); // 拼多多店铺授权
    Route::any('shopauth/AsynProcess','api/shopauth/AsynProcess'); // 拼多多异步执行文件
    Route::any('shopauth/Asynpddcate','api/shopauth/Asynpddcate'); // 拼多多异步执行文件
    /****************店铺登录************/

    Route::any('pnduoduo/getShopInfo','api/pnduoduo/getShopInfo');
    Route::any('pnduoduo/Ansypddupload','api/pnduoduo/Ansypddupload');//拼多多异步操作
    Route::post('pnduoduo/pdduploadinfo','api/pnduoduo/pdduploadinfo'); // 上传图片详情

    Route::any('pnduoduo/pdduploadpic','api/pnduoduo/pdduploadpic'); // 导入上传
    Route::post('pnduoduo/pddaddsave','api/pnduoduo/pddaddsave'); // 导入上传
    Route::any('pnduoduo/getTemplates','api/pnduoduo/getTemplates'); // 获取拼多多运费模板
    Route::post('pnduoduo/pddgoodsadd','api/pnduoduo/pddgoodsadd'); // 定义POST请求路由规则

    Route::any('test/pddadd','api/test/pddadd'); // 获取拼多多店铺


//    Route::get('api/getcode','api/auth.PddController/login'); // 定义POST请求路由规则
});

//Route::get('api/index/index','api/index/index');
//Route::get('api/index/download','api/index/download');
