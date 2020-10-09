<?php

namespace app\api\controller;

use app\BaseController;
use app\Request;
use think\facade\Cache;

class Test extends BaseController
{
    //商品发布
    public function pddadd(){
        pcntl_fork();

// 创建管道
        $sPipePath = "my_pipe.".posix_getpid();
        if (!posix_mkfifo($sPipePath, 0666)) {
            die("create pipe {$sPipePath} error");
        }

        // 模拟任务并发
        for ($i = 0; $i < 10; ++$i ) {
            $nPID = pcntl_fork(); // 创建子进程
            if ($nPID == 0) {
                // 子进程过程
                sleep(rand(1,4)); // 模拟延时
                $oW = fopen($sPipePath, 'w');
                fwrite($oW, $i."\n"); // 当前任务处理完比，在管道中写入数据
                fclose($oW);
                exit(0); // 执行完后退出
            }
        }
// 父进程
        $oR = fopen($sPipePath, 'r');
        stream_set_blocking($oR, FALSE); // 将管道设置为非堵塞，用于适应超时机制
        $sData = ''; // 存放管道中的数据
        $nLine = 0;
        $nStart = time();
        while ($nLine < 10 && (time() - $nStart) < 4) {
            $sLine = fread($oR, 1024);
            if (empty($sLine)) {
                continue;
            }

            echo "current line: {$sLine}\n";
            // 用于分析多少任务处理完毕，通过‘\n’标识
            foreach(str_split($sLine) as $c) {
                if ("\n" == $c) {
                    ++$nLine;
                }
            }
            $sData .= $sLine;
        }
        echo "Final line count:$nLine\n";
        fclose($oR);
        unlink($sPipePath); // 删除管道，已经没有作用了

// 等待子进程执行完毕，避免僵尸进程
        $n = 0;
        while ($n < 10) {
            $nStatus = -1;
            $nPID = pcntl_wait($nStatus, WNOHANG);
            if ($nPID > 0) {
                echo "{$nPID} exit\n";
                ++$n;
            }
        }

// 验证结果，主要查看结果中是否每个任务都完成了
        $arr2 = array();
        foreach(explode("\n", $sData) as $i) {// trim all
            if (is_numeric(trim($i))) {
                array_push($arr2, $i);
            }
        }
        $arr2 = array_unique($arr2);
        if ( count($arr2) == 10) {
            echo 'ok';
        } else {
            echo  "error count " . count($arr2) . "\n";
            var_dump($arr2);
        }
        echo $sPipePath;die;
        $arrint = [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15];//假设很多
        $arrint = array_chunk($arrint,4,TRUE);
        for ($i = 0; $i < 4; $i++){
            $pid = pcntl_fork();
            if ($pid == -1) {
                die("could not fork");
            } elseif ($pid) {
                echo $pid.'666666666';
                echo "I'm the Parent $i\n";
            } else {
                // 子进程处理
                // $content = file_get_contents("prefix_name0".$i);
                $psum = array_sum($arrint[$i]);
                echo $psum . "\n" .'55';//分别输出子进程的部分求和数字，但是无法进行想加，因为进程互相独立
//     	        exit;// 一定要注意退出子进程,否则pcntl_fork() 会被子进程再fork,带来处理上的影响。
        	}
        }
        // 等待子进程执行结
        while (pcntl_waitpid(0, $status) != -1) {
            $status = pcntl_wexitstatus($status);
            echo "Child $status completed\n";
        }
//        $goodsid = [
//            'https://www.baidu.com',
//            "https://www.mi.com",
//            "https://www.qq.com"
//        ];
//        $ids = [];
//
//        foreach ($goodsid as $url) {
//            $ids[] = $pid = pcntl_fork();
//            if ($pid === -1) {
//                echo "failed to fork!\n";
//                exit;
//            } elseif ($pid) {
//                pcntl_wait($status);
//            } else {
//                echo "start get url: ".$url."\n";
//                $this ->  crawler($url);
//                exit;
//            }
//        }
//        foreach ($ids as $i => $pid) {
//            if ($pid) {
//                pcntl_waitpid($pid, $status);
//            }
//        }

    }
    //爬取网页，取出网页标题
    public function crawler($url)
    {

        $content = file_get_contents($url);

        preg_match("/<title>(.*)<\/title>/", $content, $matches);

        echo $matches[1]."\n";
    }

//        foreach ($ids as $i => $pid) {
//        if ($pid) {
//        pcntl_waitpid($pid, $status);
//        }
//    }

//tp 进程处理
public function work(){
    set_time_limit(0);
    $forkNums = 20; //开启的进程数
    if (!function_exists("pcntl_fork")) {
        die("pcntl extention is must !");
    }

    for($i=0;$i<$forkNums;$i++){
        $pid = pcntl_fork();    //创建子进程
        if ($pid == -1) {
            //错误处理：创建子进程失败时返回-1.
            die('could not fork');
        } else if ($pid) {
            //父进程会得到子进程号，所以这里是父进程执行的逻辑
            //如果不需要阻塞进程，而又想得到子进程的退出状态，则可以注释掉pcntl_wait($status)语句，或写成：
            pcntl_wait($status,WNOHANG); //等待子进程中断，防止子进程成为僵尸进程。
        } else {
            //这里写子进程执行的逻辑
            $list = $this->mysql($v['start'],$v['rows']);
            foreach($list as $key=>$value){
                $terminals = $this->getterminalinfo($value); //这里调用第三方接口，该过程大概需要3s
                // ...  这里再对获取到的卡号信息进行自己相关的业务处理
            }
            unset($list);
            exit(0);
        }

    }
}
    public function index(Request $request)
    {
        $post = $request->post();
        $arr = explode(',', $post['goodsids']);
        $a = array_shift($arr);
        $array = [];
//        $obapi = $this -> openkey();
        do{
            //sleep(1); // 按设置的时间等待一小时循环执行
            //$data = $this->gooddelties($a);
            /*$taoabo_detile = $obapi->exec(
                array(
                    "api_type" => "taobao",
                    "api_name" => "item_get",
                    "api_params" => array(
                        'num_iid' => $a,
                        'is_promotion' => '1',
                    )
                )
            );
            $api_cat = $obapi->exec(
                array(
                    "api_type" => "taobao",
                    "api_name" => "item_cat_get",
                    "api_params" => array(
                        'num_iid' => $a,
                    ))
            );
            dump($taoabo_detile);
            dump($api_cat);*/
            $res = $this->gooddelties($a);
            $row = serialize($res);
            $array[] = $row;
        }while($a = array_shift($arr));
        Cache::set('aaaaaaaaaaa', $array);
        return json_encode(['code' => 200, 'msg' => '采集成功', 'status' => 'ok']);
//        while (array_shift($arr)) {
//            echo array_shift($arr);
//        }
    }

    protected function gooddelties($goodsid){
        $obapi = $this -> openkey();
        $taoabo_detile = $obapi->exec(
            array(
                "api_type" => "taobao",
                "api_name" => "item_get",
                "api_params" => array(
                    'num_iid' => $goodsid,
                    'is_promotion' => '1',
                )
            )
        );
        $api_cat = $obapi->exec(
            array(
                "api_type" => "taobao",
                "api_name" => "item_cat_get",
                "api_params" => array(
                    'num_iid' => $goodsid,
                ))
        );
        if($taoabo_detile['error'] == "item-not-found" || empty($api_cat['item']['cat_name'])){
            return false;
        }
        $tbaodetile['cid'] = $taoabo_detile['item']['cid'];// 分类id
        $tbaodetile['rootCatId'] = $taoabo_detile['item']['rootCatId'];//顶级分类ID
        $tbaodetile['root_cat_name'] = $api_cat['item']['root_cat_name'];//父级分类名称
        $tbaodetile['cat_name'] = $api_cat['item']['cat_name'];//分类名称
        $tbaodetile['num_iid'] = $taoabo_detile['item']['num_iid'];
        $tbaodetile['title'] = $taoabo_detile['item']['title'];
        $tbaodetile['price'] = $taoabo_detile['item']['price'];
        $tbaodetile['orginal_price'] = $taoabo_detile['item']['orginal_price'];
        $tbaodetile['nick'] = $taoabo_detile['item']['nick'];
        $tbaodetile['num'] = $taoabo_detile['item']['num'];
        $tbaodetile['min_num'] = $taoabo_detile['item']['min_num'];
        $tbaodetile['detail_url'] = $taoabo_detile['item']['detail_url'];
        $tbaodetile['brand'] = $taoabo_detile['item']['brand'];//品牌名称
        $tbaodetile['brandId'] = $taoabo_detile['item']['brandId'];//品牌ID
        $tbaodetile['desc'] = $taoabo_detile['item']['desc'];
        $tbaodetile['desc_img'] = $taoabo_detile['item']['desc_img'];
        $tbaodetile['item_imgs'] = $taoabo_detile['item']['item_imgs'];
        $tbaodetile['item_weight'] = $taoabo_detile['item']['item_weight'];
        $tbaodetile['location'] = $taoabo_detile['item']['location'];
        $tbaodetile['post_fee'] = $taoabo_detile['item']['post_fee'];
        $tbaodetile['express_fee'] = $taoabo_detile['item']['express_fee'];
        $tbaodetile['ems_fee'] = $taoabo_detile['item']['ems_fee'];
        $tbaodetile['shipping_to'] = $taoabo_detile['item']['shipping_to'];
        $tbaodetile['has_discount'] = $taoabo_detile['item']['has_discount'];
        $tbaodetile['video'] = $taoabo_detile['item']['video'];
        $tbaodetile['is_promotion'] = $taoabo_detile['item']['is_promotion'];
        $tbaodetile['props_name'] = $taoabo_detile['item']['props_name'];
        $tbaodetile['prop_imgs'] = $taoabo_detile['item']['prop_imgs']['prop_img'];
        $tbaodetile['props'] = $taoabo_detile['item']['props'];
        $tbaodetile['total_sold'] = $taoabo_detile['item']['total_sold'];
        $tbaodetile['skus_list'] = $taoabo_detile['item']['skus']['sku'];
        $tbaodetile['seller_id'] = $taoabo_detile['item']['seller_id'];
        $tbaodetile['sales'] = $taoabo_detile['item']['sales'];
        $tbaodetile['props_list'] = $taoabo_detile['item']['props_list'];
        $tbaodetile['tmall'] = $taoabo_detile['item']['tmall'];
        $tbaodetile['shopinfo'] = $taoabo_detile['item']['shopinfo'];
        $tbaodetile['props_img'] = $taoabo_detile['item']['props_img'];
        $tbaodetile['shopinfo'] = $taoabo_detile['item']['shopinfo'];
        $tbaodetile['shop_id'] = $taoabo_detile['item']['shop_id'];
        $tbaodetile['seller_info'] = $taoabo_detile['item']['seller_info'];
        return $tbaodetile;
    }

    protected function openkey(){
        $method = "GET";
        // 请求示例 url 默认请求参数已经URL编码处理
        //定义缓存目录和引入文件
        define("DIR_RUNTIME","runtime/");
        define("DIR_ERROR","runtime/");
        define("SECACHE_SIZE","0");
        $key = "tel18202970012";
        $secret = "20200619";
        $obapi = new \otao\ObApiClient();
        $obapi->api_url = "http://api.onebound.cn/";
        $obapi->api_urls = array("http://api.onebound.cn/","http://api-1.onebound.cn/");//备用API服务器
        $obapi->api_urls_on = true;//当网络错误时，是否启用备用API服务器
        $obapi->api_key =$key;
        $obapi->api_secret = $secret;
        $obapi->api_version ="";
        $obapi->secache_path ="runtime/";
        $obapi->secache_time ="86400";
        $obapi->cache = true;
        return $obapi;
    }
    
}
