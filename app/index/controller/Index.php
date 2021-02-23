<?php
namespace app\index\controller;

namespace app\index\controller;
use app\index\model\MMd5;
use app\index\model\MRu;
use app\index\model\MUser;
use think\cache\driver\Redis;
use think\Controller;
use think\JWT;

class Index extends Controller
{
    public  $redis;
    public function login()
    {
        //初始化redis
        $this->redis = new Redis();
        //
        $password = md5(md5(trim(input("pwd"))));
        $user = new MUser();
        $ru = new MRu();
        $account = $user->where( "password",$password)->field("user_id")->find();
//        $role_id = $ru->where("user_id",$account['user_id'])->field("role_id")->find();
        if(!empty($account)){

            //token生成
            $nowtime = time();
            $token = array(
                'iss' => 'http://www.wuruyue.com', //签发者
                'aud' => 'http://www.wuruyue.com', //jwt所面向的用户
                'iat' => $nowtime, //签发时间
                'nbf' => $nowtime, //在什么时间之后该jwt才可用
                'exp' => $nowtime + 1440, //过期时间-10min
                'data' => [
                    'userid' => $account['user_id'],
                    'pwd'=>$password
                ]
            );
            $jwt = JWT::encode($token,"nokey_0");
            //

            if($account['user_id'] != 1){
                $ret['errno'] = 202;
                $ret['errmsg'] = '登录成功';
                $ret['token'] = $jwt;
            } else {
                $ret['errno'] = 201;
                $ret['errmsg'] = '登录成功';
                $ret['token'] = $jwt;
            }
            $this->redis->set("Authorization:".md5($jwt),$account['user_id'],1440);
            $this->redis->set("Authorization:role:".md5($jwt),$account['user_id'],1440);
        } else {
            $ret['errno'] = 400;
            $ret['errmsg'] = '登录失败';
        }
        return json_encode($ret);
    }
    public function getMd5Encode()
    {
        $redis = new Redis();
        $str = input('str');
        $res = $redis->get($str);
        if($res){
            $ret = array();
            $ret['str'] = $res;
        } else {
            $md5 = new MMd5();
            $ret = $md5->where("md5str", $str)->find();
            if (!empty($ret)) {
                $redis->set($str, $ret['str']);
            }
        }
        return json_encode($ret);
    }

}