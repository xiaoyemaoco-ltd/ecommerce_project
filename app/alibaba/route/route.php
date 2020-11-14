<?php

use think\facade\Route;
//alibaba授权
Route::get('back','handleData/callBack');
Route::get('getCateByKeyword','handleData/getCategoryByKeyword');
Route::get('rootCate','handleData/getRootCategory');
Route::get('leafCate','handleData/getLeafCategory');
Route::post('test','handleData/test');
Route::get('getproduct','handleData/getProduct');
Route::get('handleGoodsAttr','handleData/handleGoodsAttr');
//商品列表
Route::get('productlist','handleData/getProductList');
//获取商品链接
Route::get('getproductlink','handleData/getProductLink');
//删除商品
Route::post('productdel','handleData/productDel');
//获取费用模板
Route::get('getfreighttemplate','handleData/getFreightTemplate');
//商品发布
Route::post('productadd','handleData/productAdd');
// 上传图片资源
Route::post('getimagesresource','handleData/imageUpload');
// 产品详情
Route::post('getproductinfo','handleData/getProductInfo');
