<?php

use think\facade\Route;
//alibaba授权
Route::get('alibaba/back','alibaba/callBack');
/*Route::get('alibaba/cate','alibaba.HandleData/categorySearchByKeyword');
//商品发布
Route::post('alibaba/productadd','alibaba.HandleData/productAdd');*/
Route::get('logintoken', 'api/logintoken');//点击跳转到授权页面

Route::get('download', 'api/download/download');//点击跳转到授权页面


Route::get('update_version', 'api/download/update_version');//点击跳转到授权页面
