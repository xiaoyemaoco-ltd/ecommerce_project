<?php

use think\facade\Route;
//alibaba授权
Route::get('back','handleData/callBack');
Route::get('getCateByKeyword','handleData/getCategoryByKeyword');
Route::get('rootCate','handleData/getRootCategory');
Route::get('leafCate','handleData/getLeafCategory');
Route::get('test','handleData/test');
Route::get('getproduct','handleData/getProduct');
Route::get('handleGoodsAttr','handleData/handleGoodsAttr');
//获取费用模板
Route::get('getfreighttemplate','handleData/getFreightTemplate');
//商品发布
Route::post('productadd','handleData/productAdd');
// 上传图片资源
Route::post('getimagesresource','handleData/getImagesResource');
// 产品详情
Route::post('getproductinfo','handleData/getProductInfo');
