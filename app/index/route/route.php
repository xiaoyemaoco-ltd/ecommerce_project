<?php
use think\facade\Route;

Route::get('swoftl', 'Swoftl/index');
Route::get('test', 'Index/test');
Route::get('process', 'Swoftl/process');
Route::get('pddcallback', 'callback/pddcallback');