<?php

namespace app\api\controller;

use think\Controller;
use think\Request;

class SystemController extends Api
{
    /**
     * 图片上传
     * @param Request $request
     * @return false|string
     */
    public function uploadImg(Request $request)
    {
        if ($request->isPost()) {
            $file = $request->file('image');
            $path = [];
            if (is_array($file)) {
                foreach($file as $v){
                    $info = $v->validate(['size'=>2097152,'ext'=>'jpg,png,gif'])->move( 'uploads');
                    if($info){
                        $path['path'][] = $request->domain() . '/uploads/' . $info->getSaveName();
                    }else{
                        // 上传失败获取错误信息
                        echo $v->getError();
                    }
                }
            } else {
                $info = $file->validate(['size'=>2097152,'ext'=>'jpg,png,gif'])->move('uploads');
                if($info){
                    // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                    $path['path'] =  $request->domain() . '/uploads/' . $info->getSaveName();
                }else{
                    // 上传失败获取错误信息
                    echo $file->getError();
                }
            }
            return success_200($path);
        }
    }
}
