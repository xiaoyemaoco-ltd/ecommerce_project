<?php
declare (strict_types = 1);
namespace app\service;
use DsfApi\PddApi1;
use think\facade\Db;
class FileSystemService extends \think\Service{
    /**
     * 注册服务
     *
     * @return mixed
     */
    public function register(){
    	//
        $this->app->bind('pddapi', function () {
//            $pddauth = Db::name('business_application_platform') -> where('name','pdd')  -> field('appkey,appsecret,redirect_uri') -> find();
            // 接口配置
            $config =[
                'clientId' => '919269592dd24bf8959bd07f4e0a569b',
                'clientSecret' => '9d92e889253e0636da0be1de6e15761505c73428',
                'memberType' => 'MERCHANT',
                'redirectUrl' =>  'http://www.sxtyyd.com/api/shopauth/pddauthlogin',
            ];
            // 从配置文件读取 Elasticsearch 服务器列表
//            $builder = new PddApi1($config);
            return new PddApi1($config);
        });

    }

    /**
     * 执行服务
     *
     * @return mixed
     */
    public function boot()
    {
        //

    }
}
