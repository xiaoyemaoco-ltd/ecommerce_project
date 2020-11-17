<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/21
 * Time: 11:03
 */
namespace app\api\controller;
use app\BaseController;
use think\Request;
use think\facade\Db;
class Download extends BaseController{

//判断是否 微信浏览器
    public function isWeixin1(){
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return "<script>location.href='" . url('api/api/share') . "'</script>";
        } else {
            return $this->download();
        }
    }

    public function share(){
        return $this->fetch();
    }

    //扫码下载聊天软件
    //http://www.sxtyyd.com/api/download/download
    //chat.zp600.com/api/api/download
    public function download($ids=''){
        //查询当前的文件路径
        $details = Db::name('app_version')->where('id', $ids)->find();
        if(empty($details)){
            return '路径不存在';
        }
        downloadFile($details['spath']);
    }


    public function update_version($edition='',$version = '',$appid = ""){
        $appid = input('appid');
        $details = Db::name('app_version') -> field('id,name,title,version_id,version_tip,spath')
            -> where(['edition'=>smsyem_version(),'appid' => $appid]) ->find();
        $data = [
            'version' => $details['version_id'],
            'version_tip' => explode('|',$details['version_tip']),
            'updownload'=> $this -> request-> domain().'/api/download?ids='.$details['id'],
        ];
        return json_encode(['status'=>'OK','data'=>$data]);
    }

    //http://chat.zp600.com/api/api/ewmdownload
    public function ewmdownload()
    {
        echo "<script> self.location='http://qr.liantu.com/api.php?text=http://chat.zp600.com/api/api/download';</script>";
    }

    //更新
    //http://chat.zp600.com/api/api/updatedownlod
    public function updatedownlod()
    {
        $app_version = smsyem_version();
        //查询当前的文件路径
        $details = Db::name('version_upgrade')->where('app_version', "Android")->find();
        $file_path = __PUBLIC__ . $details['uppath'];
        if (file_exists($file_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($file_path));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            ob_clean();
            flush();
            readfile($file_path);
            exit;
        }
    }


    //检测更新接口(电脑端)
    public function check_versiono()
    {
        $app_version = $this->smsyem_version();
        // 获取版本升级信息
        $versionUpgrade = Db::name('version_upgrade')->where('app_version', $app_version)->field('version_id,type,status,upgrade_point')->find();
        // $versionUpgrade = Db::name('version_upgrade') -> where('app_version',$app_version) -> find();
        if ($versionUpgrade) {
            $version = Db::name('version_content')->find($versionUpgrade['version_id']);
            $versionUpgrade['update_count'] = $version['update_count'];
            return apiSuccess($versionUpgrade, '版本升级信息获取成功');
        } else {
            return apiFail('400', '版本升级信息获取失败');
        }
    }

    //检测更新接口(安卓独用)
    public function check_versions()
    {
        //$app_version  = $this -> smsyem_version();
        $versionId = (int)input('version_id');
        if (empty($versionId)) {
            return apiFail('401', '参数不能为空');
        }
        // 获取版本升级信息
        $versionUpgrade = Db::name('version_upgrade')->where('app_version', 'Android')->find();
        // $versionUpgrade = Db::name('version_upgrade') -> where('app_version',$app_version) -> find();
        if ($versionUpgrade) {
            $version = Db::name('version_content')->find($versionUpgrade['version_id']);
            $versionUpgrade['update_count'] = strip_tags($version['update_count']);
            // 要升级 并且 当前版本号小于要升级的版本号
            if ($versionUpgrade['type'] && $versionId < $versionUpgrade['version_id']) {
                // 要升级
                $upcode = 1;
                $data = [
                    'upcode' => $upcode,
                    'data' => $versionUpgrade
                ];
                return apiSuccess($data, '版本升级信息获取成功');
            } else {
                // 不升级
                $upcode = 0;
                $data = [
                    'upcode' => $upcode,
                    'data' => $versionUpgrade
                ];
                return apiSuccess($data, '没有更新的内容');
            }
        } else {
            return apiFail('400', '版本升级信息获取失败');
        }

    }

    //语音上传
    public function sendfriendArm()
    {
        $file = $_FILES['arm'];
        $size = input('size');
        $send_id = input('send_id');
        $friend_id = input('friend_id');
        $exten = pathinfo($file['tmp_name'], PATHINFO_EXTENSION);
        if ($file && $exten = 'mp3') {
            //拼接文件名(发送者id-朋友id-时间戳_文件大小)
            $name = $send_id . '-' . $friend_id . '-' . time() . '_' . $size;
            $dir = date('Ymd', time());//文件目录
            //拼凑目录
            $arm_name = 'uploads/voice/' . $dir;
            if (!is_dir($arm_name)) {
                //如果不存在就创建该目录
                mkdir($arm_name, 0777, true);
            }

            $arminfo = '/' . $name . '.' . $exten;
            $arm_file = $arm_name . $arminfo;
            //拼凑路径
            $dir = $dir . $arminfo;
            //服务器文件存储路径
            $yido = move_uploaded_file($file['tmp_name'], $arm_file);
            if ($yido) {
                //$shll = 'cd /www/wwwroot/XBF/weiliaofuwu/public/'.$arm_name.' && echo y | ffmpeg -i '.$name . '.'.$exten.' '.$name . '.'.$exten;
                //exec($shll,$err,$status);
                //dump($err);
                ////if ($status==0){
                //$src为文件路径
                return json_encode(array('code' => 1, 'msg' => '上传成功', 'data' => $arm_file));
                //}
            }
            return json_encode(array('code' => 0, 'msg' => '上传失败', 'data' => null));
        }
        return json_encode(array('code' => -1, 'msg' => '未找到文件', 'data' => null));
    }

    //更新安卓友盟凭证
    public function token()
    {
        $token = input('device_tokens');
        $uid = input('uid');
        if ($token && $uid) {
            if (Db::name('user')->where('id', $uid)->update(['deviceToken' => $token])) {
                return json_encode(array('code' => 1, 'msg' => '更新成功', 'data' => null));
            }
            return json_encode(array('code' => 0, 'msg' => '更新失败', 'data' => null));

        }
        return json_encode(array('code' => 0, 'msg' => '更新失败', 'data' => null));
    }





}
