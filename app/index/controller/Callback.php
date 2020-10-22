<?php

namespace app\index\controller;
use app\BaseController;
class Callback extends BaseController{
    public function pddcallback(){
        $pddtoken = $_GET['pddtoken'];
        $this -> assign('pddtoken',$pddtoken);
        return  $this -> fetch();
    }


}