<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/28
 * Time: 16:08
 */
namespace app\api\controller;
use DsfApi\PddApi;
use fast\Redis;
use think\App;
use think\facade\Db;
use think\facade\Session;
use app\Request;
class Shopauth extends Api {
    protected $noNeedToken = [''];
    //点击跳转到授权页面
    public function pddbutton(){
        $userid = $this -> uid ?? 1;//用户ID
        $url = app('pddapi') -> authorizationRedirect($userid);
        return $this -> success('200','',['url' => $url,'is_web'=>1]);
    }
    //拼多多授权登录
    public function pddauthlogin(Request $request){
        $pddtokenData = app('pddapi') ->getAccessToken();
        $userid = $pddtokenData['uid'];//用户ID
        $resArr = json_decode($pddtokenData['pddinfo'],true);
        if(empty($pddtokenData)){
            exit(json_encode( $this -> error('401','access_token过期，请重新登录!')));
        }else{
            $res = $this -> getshopadd($userid,$resArr,$request->domain());
            if($res == 1){
                return redirect($request->domain() .'/index/pddcallback?pddtoken='.md5($resArr['access_token']));
            }
        }
    }


    //添加店铺
    public function getshopadd($userid,$resArr,$domain){
        //判断该店铺是否授权过
        $user = Db::name("shop_open_user")
            ->where(['owner_id'=>$resArr['owner_id'],'owner_name'=>$resArr['owner_name']])
            ->find();
        //获取店铺信息
        $shopInfo  = app('pddapi')  -> request('pdd.mall.info.get',$resArr['access_token']);
        if (empty($shopInfo["mall_info_get_response"])){
            header('Location:'."https://www.sxtyyd.com");
        }
        /***店铺信息S*************************************/
        $userInfo['logo'] = $shopInfo["mall_info_get_response"]['logo'];
        $userInfo['mall_name'] = $shopInfo["mall_info_get_response"]['mall_name'];
        $userInfo['mall_desc'] = $shopInfo["mall_info_get_response"]['mall_desc'];
        /***店铺信息E*************************************/
        $userInfo['shop_type'] = 'pdd';//属于哪个平台
        $userInfo['owner_id'] = $resArr['owner_id'];
        $userInfo['owner_name'] = $resArr['owner_name'];
        $userInfo['login_time'] = date('Y-m-d H:i:s');
        $userInfo['access_token'] = $resArr['access_token'];
        $userInfo['expires_in'] = $resArr['expires_in'];
        $userInfo['refresh_token'] = $resArr['refresh_token'];
        $userInfo['scope'] = json_encode($resArr['scope']);
        $userInfo['is_token'] = 1;//授权状态
        $userInfo['is_cookie'] = '0';
        $userInfo['endtime'] = time();
        $userInfo['uid'] = $userid;
        /***记录登录日志S*************************************/
        $loginLog['shop_type'] = 'pdd';
        $loginLog['uid'] = $userid;
        $loginLog['owner_id'] = $resArr['owner_id'];
        $loginLog['mall_name'] = $shopInfo["mall_info_get_response"]['mall_name'];
        $loginLog['open_info'] = json_encode($resArr);
        Db::name("shop_login_log")->insert($loginLog);
        /***记录登录日志E*************************************/
        ///店铺之前未授权信息初始化S//////////////////////////////////////////////////
        if(empty($user)){
            $userInfo['is_lock'] = '0';
            $userInfo['state'] = '0';
            $userInfo['login_num'] = 1;
            $userInfo['lock_time'] = date('Y-m-d H:i:s', strtotime('7 days'));
            $userInfo['cookie_expires_time'] = date('Y-m-d H:i:s');//cookie被封时间
            ///最后生成增量任务时间S////////////////////////////////////////////////////
            $addRes = Db::name("shop_open_user") -> insert($userInfo);
            //用户信息存入session
            Session::set('shop_user',$userInfo);
            ///新增请求执行任务等创建E//////////////////////////////////////////////////////
            $data['owner_id'] = $resArr['owner_id'];
            $data['mall_name'] = $shopInfo["mall_info_get_response"]['mall_name'];
            $this->request_by_fsockopen($domain.'/api/shopauth/AsynProcess',$data,false);
            if ($addRes){
                // $this -> pddshopshtml($shopInfo["mall_info_get_response"],$userid,$resArr['owner_id']);
                return 1;
            }else{
                return 0;
            }
            ///店铺之前未授权信息初始化E//////////////////////////////////////////////////
        }else{
            //更新拼多多类目
            $data['accessToken'] = $resArr['accessToken'];
            $data['mall_name'] = $shopInfo["mall_info_get_response"]['mall_name'];
//            echo Db::name('pdd_goods_cat') -> getlastSql();die;
//            $result  = app('pddapi')->request('pdd.goods.cats.get',$data['accessToken'],['parent_cat_id'=>0]);
//            $this->request_by_fsockopen($domain.'/api/shopauth/AsynProcess',$data,false);
            $this->request_by_fsockopen($domain.'/api/shopauth/Asynpddcate',$data,false);
//            dump($domain);die;
            $userInfo['login_num'] = $user['login_num']+1;
            $updateRes = Db::name("shop_open_user")->where(['shop_type'=>'pdd','owner_id'=>$resArr['owner_id']])->update($userInfo);
            if ($updateRes){
                $updatedUser = Db::name("shop_open_user")->where(['shop_type'=>'pdd','owner_id'=>$resArr['owner_id']])->find();
                //用户信息存入session
//                epre($updatedUser);exit;
                Session::set('shop_user',$updatedUser);
                // $this -> pddshopshtml($shopInfo["mall_info_get_response"],$userid,$resArr['owner_id']);
                return 1;
//                return $this->redirect('index/Home/index',['code' => $state]);
            }else{
                return 0;
            }
        }
    }
    public function Asynpddcate(Request $request){
        header("content-type:text/html;charset=utf-8");
        $param = $request->param();
        $this -> pddcate($param['accessToken']);
    }

    /* 获取拼多多类目树
      * $pid  父类id
      */
    public function pddcate($accessToken){
        //判断一级类目是否存在
//        $treecate1 = Db::name('pdd_goods_cat') -> where('level',1) -> select() -> toArray();
//        if(empty($treecate1)){
//            //获取一级类目
//            $cate1 = $this -> PddcateTree($accessToken,0);
//            foreach ($cate1 as $key => $val){
//                $val['end_time'] = getday()['month'];
//                $res = Db::name('pdd_goods_cat') -> where('cat_id',$val['cat_id']) -> find();
//                if(empty($res)){
//                    Db::name('pdd_goods_cat') -> insert($val);
//                }
//            }
//        }else{
//            $catetreetime3 = Db::name('pdd_goods_cat') -> where('level',3) -> value('end_time');
//            $cate1 = $this -> PddcateTree($accessToken,0);
//            foreach ($cate1 as $k1=>$v2){
//                $v2['end_time'] = getday()['month'];
//                Db::name('pdd_goods_cat') -> where('cat_id',$v2['cat_id']) -> update($v2);
//            }
//        }
        //判断二级分类
//        $treecate2 = Db::name('pdd_goods_cat') -> where('level',2)  -> select()-> toArray();
//        $cate2 =[];
//        if(empty($treecate2)){
//            foreach ($treecate1 as $key => $val){
//                $cate21[$key] =  $this -> PddcateTree($accessToken,$val['cat_id']);//self::PddcateTree($val['cat_id']);
//                $cate2 = array_merge_recursive($cate2,$cate21[$key]);
//            }
//            foreach ($cate2 as $key => $val){
//                $val['end_time'] = getday()['week'];
//                $res = Db::name('pdd_goods_cat') -> where('cat_id',$val['cat_id']) -> find();
//                if(empty($res)){
//                    Db::name('pdd_goods_cat') -> insert($val);
//                }
//            }
//        }else{
            //查询二级分类到期更新时间
//            $catetreetime2 = Db::name('pdd_goods_cat') -> where('level',2) -> value('end_time');
//            if(time() - strtotime($catetreetime2) > 0){
//                foreach ($treecate1 as $key => $val){
//                    sleep(1);
//                    $cate2[$key] = $this -> PddcateTree($accessToken,$val['cat_id']);;
//                    $cate3 = array_merge_recursive($cate3,$cate2[$key]);
//                }
//                foreach ($cate3 as $k1=>$v2){
//                    $v2['end_time'] = getday()['week'];
//                    Db::name('pdd_goods_cat') -> where('cat_id',$v2['cat_id']) -> update($v2);
//                }
//            }
//        }
        //判断三级分类
        $treecate3 =  Db::name('pdd_goods_cat') -> where('level',3)  -> select()-> toArray();
//        $cate3 = [];
//        if(empty($treecate3)){
//            foreach ($treecate2 as $key => $val){
//                $cate31[$key] =  $this -> PddcateTree($accessToken,$val['cat_id']);//self::PddcateTree($val['cat_id']);
//                $cate3 = array_merge_recursive($cate3,$cate31[$key]);
//            }
//            foreach ($cate3 as $key => $val){
//                $val['end_time'] = getday()['week'];
//                $res = Db::name('pdd_goods_cat') -> where('cat_id',$val['cat_id']) -> find();
//                if(empty($res)){
//                    Db::name('pdd_goods_cat') -> insert($val);
//                }
//            }
//        }
        //判断四级分类
        $treecate4 = Db::name('pdd_goods_cat') -> where('level',4) -> select()  -> toArray();
        $cate4 =[];
        foreach ($treecate3 as $key => $val){
            $cate41[$key] =  $this -> PddcateTree($accessToken,$val['cat_id']);//self::PddcateTree($val['cat_id']);
            $cate4 = array_merge_recursive($cate4,$cate41[$key]);
        }
        foreach ($cate4 as $key => $val){
            $val['end_time'] = getday()['week'];
            $res = Db::name('pdd_goods_cat') -> where('cat_id',$val['cat_id']) -> find();
            if(empty($res)){
                Db::name('pdd_goods_cat') -> insert($val);
            }
        }
//        if(empty($treecate4)){
//            foreach ($treecate3 as $key => $val){
//                $cate41[$key] =  $this -> PddcateTree($accessToken,$val['cat_id']);//self::PddcateTree($val['cat_id']);
//                $cate4 = array_merge_recursive($cate4,$cate41[$key]);
//            }
//            foreach ($cate4 as $key => $val){
//                $val['end_time'] = getday()['week'];
//                $res = Db::name('pdd_goods_cat') -> where('cat_id',$val['cat_id']) -> find();
//                if(empty($res)){
//                    Db::name('pdd_goods_cat') -> insert($val);
//                }
//            }
//        }
    }

    /* 获取拼多多类目树
           * $pid  父类id
           */
    public  function PddcateTree($accessToken,$pid){
        $cate = [];
        $result  = app('pddapi')->request('pdd.goods.cats.get',$accessToken,['parent_cat_id'=>$pid]);
        foreach ($result['goods_cats_get_response']['goods_cats_list'] as $key => $val){
            $cate[$key]['level'] = $val['level'];
            $cate[$key]['cat_id'] = $val['cat_id'];
            $cate[$key]['parent_cat_id'] = $val['parent_cat_id'];
            $cate[$key]['cat_name'] = $val['cat_name'];
        }
        return $cate;
    }
    /**
     * 异步请求函数
     * @param $url
     * @param array $post_data
     * @param bool $debug
     * @return bool
     */
    public function request_by_fsockopen($url,$post_data=array(),$debug=false){
        $url_array = parse_url($url);
        $hostname = $url_array['host'];
        $port = isset($url_array['port'])? $url_array['port'] : 80;
        //        @$requestPath = $url_array['path'] ."?". $url_array['query'];
        @$requestPath = $url_array['path'];
        $fp = fsockopen($hostname, $port, $errno, $errstr, 10);
        if (!$fp) {
            echo "$errstr ($errno)";
            return false;
        }
        $method = "GET";
        if(!empty($post_data)){
            $method = "POST";
        }
        $header = "$method $requestPath HTTP/1.1\r\n";
        $header.="Host: $hostname\r\n";
        if(!empty($post_data)){
            $_post = strval(NULL);
            unset($_post);
            foreach($post_data as $k => $v){
                $_post[]= $k."=".urlencode($v);//须做url转码以防模拟post提交的数据中有&符而导致post参数键值对紊乱
            }
            $_post = implode('&', $_post);
            $header .= "Content-Type: application/x-www-form-urlencoded\r\n";//POST数据
            $header .= "Content-Length: ". strlen($_post) ."\r\n";//POST数据的长度
            $header.="Connection: Close\r\n\r\n";//长连接关闭
            $header .= $_post; //传递POST数据
        }else{
            $header.="Connection: Close\r\n\r\n";//长连接关闭
        }
        fwrite($fp, $header);
        //-----------------调试代码区间-----------------
        //注如果开启下面的注释,异步将不生效可是方便调试
        if($debug){
            $html = '';
            while (!feof($fp)) {
                $html.=fgets($fp);
            }
            echo $html;
        }
        //-----------------调试代码区间-----------------
        fclose($fp);
    }

    public function AsynProcess(Request $request){
        header("content-type:text/html;charset=utf-8");
        $param = $request->param();
//        $this -> pddcate($param['accessToken']);
        //初次授权生成八天的初始化订单任务
        $this->InitializeOrderTask($param['owner_id']);
        //初次授权生成八天的初始化评价任务
//        $this->InitializeEvaluateTask($param['owner_id']);
//        //初次授权生成一条dsr任务
//        $this->InitializeDsrTask($param['owner_id']);
//        //初次授权生成七天数据统计和dsr统计
//        $this->InitializeStatistics($param['owner_id']);
//        //初次授权记录初始期望dsr值
//        $this->saveInitDsr($param['owner_id']);
//        //初次授权记录免费试用消费记录
        $this->saveFreeRecord($param['owner_id'],$param['mall_name']);
    }

    /**
     * purchase_record表
     * 当店铺初次授权生成初始化免费试用记录
     * @param $ownerId
     * @param $mallName
     * @throws \think\Exception
     */
    public function saveFreeRecord($ownerId,$mallName){
//        $db2 = Db::connect('pfpartner2');
        $initRecord['owner_id'] = $ownerId;
        $initRecord['mall_name'] = $mallName;
        $initRecord['data_date'] = date('Y-m-d');
        $initRecord['product_name'] = '免费试用';
        $initRecord['product_type'] = '2';
        $initRecord['payment_method'] = '2';
        $initRecord['product_details'] = '免费试用';
        $initRecord['start_time'] = date('Y-m-d H:i:s');
        $initRecord['end_time'] = date('Y-m-d H:i:s', strtotime('7 days'));
        $initRecord['day'] = '7';
        $initRecord['original_price'] = '0';
        $initRecord['discount_price'] = '0';
        $initRecord['actual_price'] = '0';
        Db::name('pdd_purchase_record')->insert($initRecord);
    }
    /**
     * dsr_statistics和data_statistics表
     * @param $ownerId
     * @throws \think\Exception
     */
    public function InitializeStatistics($ownerId){
//        $db2 = Db::connect('pfpartner2');
        $yesterday = strtotime(date('Y-m-d',strtotime('-2 days')));
        $eightDaysAgo = strtotime(date('Y-m-d',strtotime('-8 days')));
        $data['owner_id'] = $ownerId;
        $data['add_order'] = 0;
        $data['logistics_order'] = 0;
        $data['remarks_order'] = 0;
        $data['evaluate_order'] = 0;
        $data['praise_order'] = 0;
        $data['effective_praise_order'] = 0;
        $data['logistics_status'] = 0;

        $dsr['owner_id'] = $ownerId;
        $dsr['dsr_score_describe'] = 0;
        $dsr['dsr_score_logistics'] = 0;
        $dsr['dsr_score_attitude'] = 0;
        $dsr['avgDescRevScrRcatePct3m'] = 0;
        $dsr['avgLgstRevScrRcatePct3m'] = 0;
        $dsr['avgServRevScrRcatePct3m'] = 0;

        for ($i = $eightDaysAgo;$i <= $yesterday;$i+=86400) {
            $data['data_date'] = date('Y-m-d',$i);
            $data['add_time'] = date('Y-m-d H:i:s');

            $dsr['data_date'] = date('Y-m-d',$i);
            $dsr['add_time'] = date('Y-m-d H:i:s');

            Db::name('pdd_dsr_statistics')->insert($dsr);
            Db::name('pdd_data_statistics')->insert($data);
        }
        $dsr['data_date'] = date('Y-m-d',strtotime('-1 days'));
        $dsr['add_time'] = date('Y-m-d H:i:s');
        Db::name('pdd_dsr_statistics')->insert($dsr);

    }

    /**
     * dsr_task表
     * @param $ownerId
     * @throws \think\Exception
     */
    public function InitializeDsrTask($ownerId){
//        $db = Db::connect('pfpartner');
        $data['owner_id'] = $ownerId;
        $data['state'] = '0';
        $data['data_date'] = date('Y-m-d');
        $data['add_time'] = date('Y-m-d H:i:s');
        Db::name('pdd_dsr_task')->insert($data);
    }

    /**
     * evaluate_order和task_evaluate_page表
     * 当店铺初次授权生成八天的初始化评价任务
     * @access public
     * @param int $owner_id 店铺id
     */
    public function InitializeEvaluateTask($ownerId)
    {   //3600 = 1h  86400 = 24h
//        $db = Db::connect('pfpartner');
        $now = strtotime(date('Y-m-d H'.':00:00'));
        $eightAgo = strtotime(date('Y-m-d',strtotime('-8 days')));
        for ($i = $eightAgo;$i < $now;$i+=3600){
            $data['owner_id'] = $ownerId;
            $data['state'] = '0';
            $data['type'] = '0';
            $data['start_time'] = date('Y-m-d H:i:s',$i);
            $data['end_time'] = date('Y-m-d H:i:s',$i+3599);;
            $data['add_time'] = date('Y-m-d H:i:s');
            $data['task_id'] = Db::name('pdd_evaluate_order')->insertGetId($data);
            unset($data['type']);
            Db::name('pdd_task_evaluate_page')->insert($data);
            unset($data);
        }
    }

    /**
     * task_order和task_orderincrement_page表
     * 当店铺初次授权生成八天的初始化订单任务
     * @access public
     * @param int $owner_id 店铺id
     */
    public function InitializeOrderTask($ownerId){
        $owner_id = $ownerId;
        for ($i=1; $i <8 ; $i++) {
            $taskArr["owner_id"] =  $owner_id;
            $taskArr["state"] =  0;
            $taskArr["type"] =  0;
            $taskArr["start_time"] =  date("Y-m-d",strtotime("-".$i." day"))." 00:00:00";
            $taskArr["end_time"] =  date("Y-m-d",strtotime("-".$i." day"))." 23:59:59";
            $taskArr["add_time"] =  date("Y-m-d H:i:s");

            $taskOrderArr["owner_id"] = $owner_id;
            $taskOrderArr["state"] = 0;
            $taskOrderArr["start_time"] =  date("Y-m-d",strtotime("-".$i." day"))." 00:00:00";
            $taskOrderArr["end_time"] =  date("Y-m-d",strtotime("-".$i." day"))." 23:59:59";
            $taskOrderArr["page"] = 1;
            $taskOrderArr["add_time"] =  date("Y-m-d H:i:s");
            $taskOrderArr["task_id"] = Db::name("pdd_task_order")->insertGetId($taskArr);
            Db::name("pdd_task_orderincrement_page")->insert($taskOrderArr);
        }

        $start = strtotime(date('Y-m-d'));
        //        $now = strtotime(date('Y-m-d 17'.':29:00'));
        $now = time();
        for ($j = $start; $j <= $now; $j+=1800){

            if ($j+1800 > $now){
                break;
            }
            $taskArr1["owner_id"] =  $owner_id;
            $taskArr1["state"] =  0;
            $taskArr1["type"] =  1;
            $taskArr1["start_time"] =  date("Y-m-d H:i:s",$j);
            $taskArr1["end_time"] =  date("Y-m-d H:i:s",$j+1800);
            $taskArr1["add_time"] =  date("Y-m-d H:i:s");

            $taskOrderArr1["owner_id"] = $owner_id;
            $taskOrderArr1["state"] = 0;
            $taskOrderArr1["start_time"] = date("Y-m-d H:i:s",$j);
            $taskOrderArr1["end_time"] =  date("Y-m-d H:i:s",$j+1800);
            $taskOrderArr1["page"] = 1;
            $taskOrderArr1["type"] = 1;
            $taskOrderArr1["add_time"] =  date("Y-m-d H:i:s");

            $taskOrderArr1["task_id"] = Db::name("pdd_task_order")->insertGetId($taskArr1);
            Db::name("pdd_task_orderincrement_page")->insert($taskOrderArr1);
            unset($taskArr1);
            unset($taskOrderArr1);
        }
    }

    /**
     * evaluation_rules表
     * 当店铺初次授权生成初始化期望dsr值
     * @param $ownerId
     * @throws \think\Exception
     */
    public function saveInitDsr($ownerId){
        $db2 = Db::connect('pfpartner2');
        $evaluationRule['owner_id'] = $ownerId;
        $evaluationRule['default_evaluate'] = '1';
        $evaluationRule['remarks_evaluate'] = '1';
        $evaluationRule['dsr_score_describe'] = '5';
        $evaluationRule['dsr_score_logistics'] = '5';
        $evaluationRule['dsr_score_attitude'] = '5';
        $db2->name('evaluation_rules')->insert($evaluationRule);
    }




}
