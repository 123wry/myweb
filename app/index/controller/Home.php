<?php
namespace app\index\controller;

use app\index\model\MArticle;
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
        $res = $menu->alias("m")->join("t_m_rm rm", "m.menu_id=rm.menu_id", "right")
            ->field("m.menu_name,m.parent_menu_id,m.menu_link,m.menu_icon,m.menu_level,m.menu_id")
            ->where("m.state", 0)
            ->where("rm.role_id", $role)
            ->select();
        $ret = array();
        $ret = $this->getMenu($res, $pids, $ret);
        return $this->ajaxReturn($ret);
    }

    private function getMenu($arr, $pid, $ret)
    {
        $flag = 0;
        $nowArr = array();
        foreach ($arr as $a) {
            if ($a['parent_menu_id'] == 0) {
                if (!in_array($a, $ret)) {
                    $flag = 1;
                    array_push($ret, $a);
                }
            } else if (in_array($a['parent_menu_id'], $pid)) {
                if (!in_array($a, $ret)) {
                    $flag = 1;
                    array_push($ret, $a);
                    array_push($nowArr, $a['menu_id']);
                }
            }
        }
        if ($flag == 0) {
            return $ret;
        } else {
            return $this->getMenu($arr, $nowArr, $ret);
        }

    }

    public function getTags()
    {
        $tag = new MTag();
        $ret = $tag->select();
        return $this->ajaxReturn($ret);
    }

    public function sendEssay()
    {
        $tag = '';
        $title = input("title");
        $tags = input("tags/a");
        $fileList = input("fileList/a");
        $files = $fileList[0]['url'];
        $editor = input("editor");
        if(empty($title)){
            $ret['errno'] = 400;
            $ret['errmsg'] = '主题不能为空';
            $this->ajaxReturn($ret);exit;
        }
        if(count($tags) == 0){
            $ret['errno'] = 400;
            $ret['errmsg'] = '标签不能为空';
            $this->ajaxReturn($ret);exit;
        }
        foreach ($tags as $t){
            $tag = $t['tag_id'].',';
        }
        $tag = trim($tag,',');
        if(count($fileList) == 0){
            $ret['errno'] = 400;
            $ret['errmsg'] = '封面不能为空';
            $this->ajaxReturn($ret);exit;
        }
        if($editor == ''){
            $ret['errno'] = 400;
            $ret['errmsg'] = '正文不能为空';
            $this->ajaxReturn($ret);exit;
        }
        $article = new MArticle();
        $mtag = new MTag();
        $article->data([
            "title"=>$title,
            "editor"=>$editor,
            "tags"=>$tag,
            "fileList"=>$files
        ]);
        $article_id = $article->save();
        foreach ($tags as $t) {
           $mtag->data([
                "tag_id" =>$t['tag_id'],
                "article_id"=>$article_id
            ]);
            $result = $mtag->save();
        }
        if(!empty($result)){
            $ret['errno'] = 400;
            $ret['errmsg'] = '提交失败';
        } else {
            $ret['errno'] = 200;
            $ret['errmsg'] = '提交成功';
        }
        $this->ajaxReturn($ret);
    }
}