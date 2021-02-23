<?php
/**
 * Created by PhpStorm.
 * User: w
 * Date: 2020/12/21
 * Time: 1:02
 */

namespace app\index\controller;


use think\cache\driver\Redis;
use think\Controller;
use think\Request;

class Base extends Controller
{
    public  $redis;
    public $flag = 0;
    public $role_id = 0;
    public $user = 0;
    public function _initialize()
    {
        $this->redis = new Redis();
        $Authorization = Request::instance()->header("Authorization");
        $redisKey = md5($Authorization);
        //24分钟没用使用此接口则返回重新登录提示
        $key = $this->redis->get("Authorization:".$redisKey);
        $this->user = $key;
        $this->role_id = $this->redis->get("Authorization:role:".$redisKey);
        if(!empty($key)) {
            $this->flag = 0;
            $this->redis->set("Authorization:" . $redisKey, $key, 1440);
            $this->redis->set("Authorization:role:" . $redisKey, $key, 1540);
        } else {
            $this->flag = 1;
        }
    }
    public function ajaxReturn($ret)
    {
        if($this->flag == 1){
            unset($ret);
            $ret['errno'] = 30001;
            $ret['errmsg'] = '请重新登录!';
        }
        return json($ret);
    }
}