<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/29
 * Time: 19:33
 */
namespace app\api\controller;
use think\facade\Db;
use think\facade\Session;
use think\facade\Event;
use fast\Random;
use app\Request;
class User extends Api{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register','forget_pass', 'resetpwd', 'changeemail', 'changemobile', 'third'];
    protected $noNeedToken = ['outlogin'];
    public function initialize(){
        parent::initialize();
    }
    /**
     * 会员中心.
     */
    public function index(){}

    /**
     * 注册会员.
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email    邮箱
     * @param string $mobile   手机号
     */
    public function register(Request $request){
        $username =  $request->post('mobile');
        $password =  $request->post('password');
        $varcode =  $request->post('varcode');
        //        $card_number = $this->request->request('card_number');
//        if(!preg_match("/1[34578]{1}\d{9}$/",$username)){
//           $this->error('402','参数格式有误');
//        }
        $code = $this -> check_varcode_prvite($username,$varcode);
        if($code == 1){
            return $this->error('401','手机号或验证码未填写');
        }else if($code == 2){
            return  $this->error('401','验证码错误');
        }else if($code == 3){
            return $this->error('401','验证码获取超时');
        }

        if (!$username || !$password) {
            return $this->error('401','账号或密码不能为空');
        }

        if(strlen($password) < 5 || strlen($password) > 20){
            return $this->error('401','密码长度至少5位，最多20位');
        }

        //查询账号是否存在
        $userinfo = Db::name('user') -> where('user_name',$username) -> find();
        if($userinfo){
            return $this->error('401','该手机号已经注册!');
        }
        $data = [
            'user_name' => $username,
            'user_pass' => md5($password),
            'mobile' => $username,
            'create_time' => time(),
            'loginip' => get_client_ip(),
            'seisonid' => md5($username.$password.get_client_ip())
//            'card_number' => $card_number,
        ];
        //账号注册时需要开启事务,避免出现垃圾数据
        Db::startTrans();
        try{
            $user_id =   Db::name('user') -> insertGetId($data);
            if($user_id>0){
                Db::name('topup') ->insert(['uid'=>$user_id,'card_number'=>'6058923568'.$user_id]);
                Db::name('user_token') -> insert(['user_id'=>$user_id]);
                user_log($username,$user_id,SOF_NAME.$username.'用户注册成功!');
                // 提交事务
                Db::commit();
            }
        } catch (\Exception $e) {
            dump($e->getMessage());die;
            Db::rollback();
            return $this->error('401','注册失败');
        }
        return $this->success('200','注册成功');
    }

    /**
     * 会员登录.
     *
     * @param string $account  账号
     * @param string $password 密码
     */
    public function login(Request $request){
        $username = $request->post('mobile');
        $password = $request->post('password');
        if (! $username || ! $password) {
            $this->error(401,'账号或密码不能为空');
        }
        $where = "u.user_name = '$username' or u.mobile='$username' or tu.card_number='$username' and user_pass='".md5($password)."'";
        $result = Db::name('user') -> alias('u') -> join('topup tu','tu.uid=u.id') -> where($where)  -> find();
        //判断账号是否存在
        if($result){
            // 判断用户是否禁用
            if($result['user_status'] != 1 ){
                return  $this->error('401','用户被禁用，请联系管理员');
            }

            //判断是否一台登录，多台不让登录
            if($result['loginip'] != get_client_ip() && $result['seisonid'] == md5($username.$password.get_client_ip()) && $result['1ogin_status'] == 1){
                return  $this->error('403','对不起，你的号已经在别的设备上登录');
            }

            $data['logincount'] = ($result['logincount'] + 1);
            $data['loginip'] = get_client_ip();
            $data['login_status'] = 1;
            $data['seisonid'] = md5($username.$password.get_client_ip());
            $data['logintime'] = time();
            $token = Random::uuid();
            $time_out = strtotime("+7 days");
            $res = Db::name('user') -> where('id',$result['id']) -> update($data);
            if($res){
                Db::name('user_token') -> where([ 'user_id'=>$result['id']]) -> update([
                    'token'=> $token,
                    'update_token_time'=> $time_out,
                ]);
//                $data2=['uid'=>$result['id'],'username'=>$username,'token'=>$token];
//                Session::set('userid',$data2);
                user_log($username,$result['id'],SOF_NAME.$username.'用户登录成功!');
                $userdata = ['token'=> $token,'username' => $username,'login_status' => 1];
                return  $this -> success('200','登录成功',$userdata);
            }else{
                return $this -> error('401','登录失败');
            }
        }else{
            return $this->error('401','账号或密码错误');
        }
    }
    /**
     * 注销登录.
     */
    public function outlogin(Request $request) {
        $username =  $request->post('mobile');
        Db::name('user') -> where('user_name',$username) -> update(['loginip'=>'','login_status'=>0]);
        Session::delete('userid');
        return $this->success('200','退出成功');
    }

        //忘记密码
    public function forget_pass(Request $request){
        $username = $request->post('mobile');
        $password = $request->post('password');
        $varcode = $request->post('varcode');
        $code = $this -> check_varcode_prvite($username,$varcode);
        if($code == 1){
            return $this->error('401','手机号或验证码未填写');
        }else if($code == 2){
            return  $this->error('401','验证码错误');
        }else if($code == 3){
            return $this->error('401','验证码获取超时');
        }
        if(empty($username)||empty($password)){
            return  $this->error('401','密码不能为空');
        }
        $where = "user_name = '$username' or mobile='$username'";
        $result = Db::name('user')-> where($where)  -> find();
        if(empty($result)){
            return  $this->error('401','账号不存在');
        }
        if($result['user_pass'] == md5($password)){
            return $this->error('401','与原密码一致');
        }
        $res = Db::name('user') -> where(['user_name'=>$username]) -> update(['user_pass'=>md5($password)]);
        if($res > 0){
            user_log($username,$result['id'],SOF_NAME.$username.'用户修改密码成功!');
            return $this->success('200','修改成功');
        }else{
            return $this->error('401','修改密码失败');
        }
    }

    //更新日志
    public function update_msg_log(Request $request){
        $type = $request->get('type');
        $swappid = $request->get('appid');
        switch ($type ){
            case 'suplog' :
                $swid = Db::name('app_version') -> where(['edition'=>smsyem_version(),'appid' => $swappid]) -> value('id');
                $swlogarry = Db::name('app_version_content')
                    -> field('id,version_tip,createtime,version_id')
                    -> where('apversionid',$swid)
                    -> order('createtime desc')
                    -> select() -> toArray();
                foreach($swlogarry as $k=> $val){
                    $swlogarry[$k]['createtime'] = date('Y-m-d',$val['createtime']);
                    $swlogarry[$k]['version_tip'] = explode('|',$val['version_tip']);
                }
                return $this->success('200','成功',$swlogarry);
                break;
        }
    }

















    /**
     * 重置密码
     *
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function resetpwd(){
        /* $type = $this->request->request('type');
         $mobile = $this->request->request('mobile');
         $email = $this->request->request('email');
         $newpassword = $this->request->request('newpassword');
         $captcha = $this->request->request('captcha');
         if (! $newpassword || ! $captcha) {
             $this->error(__('Invalid parameters'));
         }
         if ($type == 'mobile') {
             if (! Validate::regex($mobile, "^1\d{10}$")) {
                 $this->error(__('Mobile is incorrect'));
             }
             $user = \app\common\model\User::getByMobile($mobile);
             if (! $user) {
                 $this->error(__('User not found'));
             }
             $ret = Sms::check($mobile, $captcha, 'resetpwd');
             if (! $ret) {
                 $this->error(__('Captcha is incorrect'));
             }
             Sms::flush($mobile, 'resetpwd');
         } else {
             if (! Validate::is($email, 'email')) {
                 $this->error(__('Email is incorrect'));
             }
             $user = \app\common\model\User::getByEmail($email);
             if (! $user) {
                 $this->error(__('User not found'));
             }
             $ret = Ems::check($email, $captcha, 'resetpwd');
             if (! $ret) {
                 $this->error(__('Captcha is incorrect'));
             }
             Ems::flush($email, 'resetpwd');
         }
         //模拟一次登录
         $this->auth->direct($user->id);
         $ret = $this->auth->changepwd($newpassword, '', true);
         if ($ret) {
             $this->success(__('Reset password successful'));
         } else {
             $this->error($this->auth->getError());
         }*/
    }




    /**
     * 修改会员个人信息.
     *
     * @param string $avatar   头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio      个人简介
     */
    public function profile(){
//        $user = $this ->getUser();
//        $username = $this->request->request('username');
//        $nickname = $this->request->request('nickname');
//        $bio = $this->request->request('bio');
//        $avatar = $this->request->request('avatar', '', 'trim,strip_tags,htmlspecialchars');
//        if ($username) {
//            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
//            if ($exists) {
//                $this->error(__('Username already exists'));
//            }
//            $user->username = $username;
//        }
//        $user->nickname = $nickname;
//        $user->bio = $bio;
//        $user->avatar = $avatar;
//        $user->save();
//        $this->success();
    }

    /**
     * 修改邮箱.
     *
     * @param string $email   邮箱
     * @param string $captcha 验证码
     */
    public function changeemail(){
//        $user = $this->auth->getUser();
//        $email = $this->request->post('email');
//        $captcha = $this->request->request('captcha');
//        if (! $email || ! $captcha) {
//            $this->error(__('Invalid parameters'));
//        }
//        if (! Validate::is($email, 'email')) {
//            $this->error(__('Email is incorrect'));
//        }
//        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
//            $this->error(__('Email already exists'));
//        }
//        $result = Ems::check($email, $captcha, 'changeemail');
//        if (! $result) {
//            $this->error(__('Captcha is incorrect'));
//        }
//        $verification = $user->verification;
//        $verification->email = 1;
//        $user->verification = $verification;
//        $user->email = $email;
//        $user->save();
//
//        Ems::flush($email, 'changeemail');
//        $this->success();
    }

    /**
     * 修改手机号.
     *
     * @param string $email   手机号
     * @param string $captcha 验证码
     */
    public function changemobile(){
//        $user = $this->auth->getUser();
//        $mobile = $this->request->request('mobile');
//        $captcha = $this->request->request('captcha');
//        if (! $mobile || ! $captcha) {
//            $this->error(__('Invalid parameters'));
//        }
//        if (! Validate::regex($mobile, "^1\d{10}$")) {
//            $this->error(__('Mobile is incorrect'));
//        }
//        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
//            $this->error(__('Mobile already exists'));
//        }
//        $result = Sms::check($mobile, $captcha, 'changemobile');
//        if (! $result) {
//            $this->error(__('Captcha is incorrect'));
//        }
//        $verification = $user->verification;
//        $verification->mobile = 1;
//        $user->verification = $verification;
//        $user->mobile = $mobile;
//        $user->save();
//
//        Sms::flush($mobile, 'changemobile');
//        $this->success();
    }

    /**
     * 第三方登录.
     *
     * @param string $platform 平台名称
     * @param string $code     Code码
     */
    public function third(Request $request){
//        $url = url('user/index');
//        $platform = $request->post('platform');
//        $code = $request->post('code');
//        $config = get_addon_config('third');
//        if (! $config || ! isset($config[$platform])) {
//           return  $this->error('401','未知参数');
//        }
//        $app = new \addons\third\library\Application($config);
//        //通过code换access_token和绑定会员
//        $result = $app->{$platform}->getUserInfo(['code' => $code]);
//        if ($result) {
//            $loginret = \addons\third\library\Service::connect($platform, $result);
//            if ($loginret) {
//                $data = [
//                    'userinfo'  => $this->auth->getUserinfo(),
//                    'thirdinfo' => $result,
//                ];
//                $this->success(__('Logged in successful'), $data);
//            }
//        }
//        $this->error(__('Operation failed'), $url);
    }







    /**
     * 手机验证码登录.
     *
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin(Request $request){
//        $mobile = $request->post('mobile');
//        $captcha = $request->post('captcha');
//        if (! $mobile || ! $captcha) {
//          return $this->error('401','未知参数');
//        }
//        if (! Validate::regex($mobile, "^1\d{10}$")) {
//            $this->error(__('Mobile is incorrect'));
//        }
//        if (! Sms::check($mobile, $captcha, 'mobilelogin')) {
//            $this->error(__('Captcha is incorrect'));
//        }
//        $user = \app\common\model\User::getByMobile($mobile);
//        if ($user) {
//            if ($user->status != 'normal') {
//                 $this->error(__('Account is locked'));
//            }
//            //如果已经有账号则直接登录
//            $ret = $this->auth->direct($user->id);
//        } else {
//            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
//        }
//        if ($ret) {
//            Sms::flush($mobile, 'mobilelogin');
//            $data = ['userinfo' => $this->auth->getUserinfo()];
//            $this->success(__('Logged in successful'), $data);
//        } else {
//            $this->error($this->auth->getError());
//        }
    }

}
