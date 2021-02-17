<?php
namespace app\index\controller;

use app\index\model\MMenu;
use app\index\model\MTag;

class Home extends Base
{
    public function _initialize()
    {
        parent::_initialize();
    }
    public function getChildMenu()
    {
        $pid = intval(input("pid"));
        $pids = array($pid);
        $role = $this->role_id;
        $menu = new MMenu();
        $res = $menu->alias("m")->join("t_m_rm rm","m.menu_id=rm.menu_id","right")
            ->field("m.menu_name,m.parent_menu_id,m.menu_link,m.menu_icon,m.menu_level,m.menu_id")
            ->where("m.state",0)
            ->where("rm.role_id",$role)
            ->select();
        $ret = array();
        $ret = $this->getMenu($res,$pids,$ret);
        return $this->ajaxReturn($ret);
    }
    private function getMenu($arr,$pid,$ret)
    {
        $flag = 0;
        $nowArr = array();
        foreach ($arr as $a){
            if($a['parent_menu_id'] == 0){
                if(!in_array($a,$ret)) {
                    $flag = 1;
                    array_push($ret,$a);
                }
            }else if(in_array($a['parent_menu_id'],$pid)){
                if(!in_array($a,$ret)) {
                    $flag = 1;
                    array_push($ret,$a);
                    array_push($nowArr,$a['menu_id']);
                }
            }
        }
        if($flag == 0){
            return $ret;
        } else {
            return $this->getMenu($arr,$nowArr,$ret);
        }

    }

    public function gettags()
    {
        $tag = new MTag();
        $ret = $tag->where("tag_status",0)->select();
        return $this->ajaxReturn($ret);
    }

}