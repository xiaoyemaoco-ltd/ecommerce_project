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
//商品发布
Route::post('productadd','handleData/productAdd');
