<?php

use think\facade\Route;
//alibaba授权
Route::get('alibaba/back','alibaba/callBack');
/*Route::get('alibaba/cate','alibaba.HandleData/categorySearchByKeyword');
//商品发布
Route::post('alibaba/productadd','alibaba.HandleData/productAdd');*/
