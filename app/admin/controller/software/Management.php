<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/8/29
 * Time: 15:33
 */
namespace app\admin\controller\software;
use app\common\controller\Backend;
use think\facade\Db;
use fast\Exclfile;
class Management extends Backend{
    protected $model = null;
    protected $modelcontent = null;
    protected $noNeedRight = ['check', 'rulelist'];
    public function initialize(){
        parent::initialize();
        $this -> model = Db::name('app_version');
        $this -> modelcontent = Db::name('app_version_content');
    }
    public function index(){
        if ($this->request->isAjax()) {
            [$where, $sort, $order, $offset, $limit] = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();
//            $sort ='sort';$order='desc';
            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select() ->toArray();
//            echo $this -> model -> getLastSql();
            $result = ['total' => $total, 'rows' => $list];
            return json($result);
        }
        return $this -> fetch();
    }

    public function add(){
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if($params){
                $params['createtime'] =time();
                $this -> model -> insert($params);
                $this->success();
            }
            $this->error();
        }
        return $this -> fetch();
    }
    public function edit($ids=null){
        $row = $this ->model ->find($ids);
        if (! $row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            if($params){
                $data =[
                    'name' => $params['name'],
                    'title' => $params['title'],
                    'edition' => $params['edition'],
                    'version_mini' => $params['version_mini'],
                    'version_id' => $params['version_id'],
                    'version_tip' => $params['version_tip'],
                    'spath' =>  $params['spath'],
                    'upspath' => $params['upspath'],
                    'sort' =>  $params['sort'],
                    'status' =>  $params['status'],
                    'updatetime' =>  time()
                ];
                $version_mini = (int)$this -> versiondx($params['version_mini']);
                $version_id = (int)$this -> versiondx($params['version_id']);
                if($version_id <= $version_mini){
                    $this->error('大版本号必须大于小版本号！');
                }
                $updata = [
                    'apversionid' => $params['id'],
                    'version_id' =>$params['version_id'],
                    'version_tip' => $params['version_tip'],
                    'content' => $params['content'],
                    'createtime' => time()
                ];
                $this ->model -> where('id',$params['id']) ->  update($data);
                $this -> modelcontent ->  where('id',$params['id']) ->  insert($updata);
                $this->success();
            }
            $this->error();

        }
        $this->assign('row', $row);
        return $this-> fetch();
    }
    /**
     * 删除.
     */
    public function del($ids = ''){
        if ($ids) {
            $this -> model -> where('id',$ids) -> delete();
            $this->success();
        }
        $this->error();
    }
    /**
     * 批量更新.
     *
     * @internal
     */
    public function multi($ids = '')
    {
        // 管理员禁止批量操作
        $this->error();
    }

    public function selectpage()
    {
        return parent::selectpage();
    }


    //上传软件
    public function upload_software(){
        $uplaodsrc = '/software/';
        $upload = new Exclfile(SHUJUCUNCHU);
        $name = input('name');
        if($name == 'pathFile'){
            $url=$upload -> software('pathFile',$uplaodsrc);
        }else if($name == 'uppathFile'){
            $url=$upload -> software('uppathFile',$uplaodsrc);
        }
        exit(json_encode(array('status'=>1,'url'=>$url)));
    }

    protected function versiondx($version){
        $arr = explode('.',$version);
        $version = $arr[0].$arr[1].$arr[2];
        return $version;
    }
}
