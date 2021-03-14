<?php
namespace app\index\controller;

use app\index\model\MArticle;
use app\index\model\MAtag;
use app\index\model\MFiles;
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
            return $this->ajaxReturn($ret);
        }
        if(count($tags) == 0){
            $ret['errno'] = 400;
            $ret['errmsg'] = '标签不能为空';
            return $this->ajaxReturn($ret);
        }
        foreach ($tags as $t){
            $tag .= $t['tag_id'].',';
        }
        $tag = trim($tag,',');
        if(count($fileList) == 0){
            $ret['errno'] = 400;
            $ret['errmsg'] = '封面不能为空';
            return $this->ajaxReturn($ret);
        }
        if($editor == ''){
            $ret['errno'] = 400;
            $ret['errmsg'] = '正文不能为空';
            return $this->ajaxReturn($ret);
        }
        $article = new MArticle();
        $matag = new MAtag();

        $article->data([
            "title"=>$title,
            "editor"=>$editor,
            "tags"=>$tag,
            "fileList"=>$files,
            "user_id"=>$this->user,
            "c_time"=>time()
        ]);
        $article->save();
        $article_id = $article->article_id;
        foreach ($tags as $t) {
           $tagList[] = array(
                "tag_id" =>$t['tag_id'],
                "article_id"=>$article_id
           );
        }
        $result = $matag->saveAll($tagList);
        if(empty($result)){
            $ret['errno'] = 400;
            $ret['errmsg'] = '提交失败';
        } else {
            $ret['errno'] = 200;
            $ret['errmsg'] = '提交成功';
        }
        return $this->ajaxReturn($ret);
    }
    public function uploadFile()
    {
        $file_ = '/public/static/tmpfile/';

        $gitinput = input("gitinput");
        $input = input("input");
        $filelist = input("fileList/s");

        $files = new MFiles();
        $files->data([
            "file_title"=>$input,
            "filelisturl"=>$filelist,
            "gitfile"=>$gitinput,
            "user_id"=>$this->user,
            "c_time"=>time()
        ]);
        $result = $files->save();
        if(empty($result)){
            $ret['errno'] = 400;
            $ret['errmsg'] = '提交失败';
        } else {
            $ret['errno'] = 200;
            $ret['errmsg'] = '提交成功';
        }
        return $this->ajaxReturn($ret);
    }

    public function addTag()
    {
        $tags = input("tags/a");
        $mtag = new MTag();
        $tagArr = array();
        $tagTemp = array();
        foreach ($tags as $t){
            if($t['tag_id'] == 0){
                $tagTemp['name'] = $t['name'];
                $tagTemp['type'] = $t['type'];
                $tagTemp['tag_type'] = $t['tag_type'];
                $tagArr[] = $tagTemp;
            }
        }
        $result = $mtag->saveAll($tagArr);
        if(empty($result)){
            $ret['errno'] = 400;
            $ret['errmsg'] = '新增标签失败';
        } else {
            $ret['errno'] = 200;
            $ret['errmsg'] = '新增标签成功';
        }
        return $this->ajaxReturn($ret);
    }
    public function getArticle()
    {
        $article = new MArticle();
        $user_id = $this->user;
        $ret = $article
            ->field("title,fileList,c_time")
            ->where("status",0)
            ->where("user_id",$user_id)
            ->select();
        foreach ($ret as $key=>$item){
            $ret[$key]['c_time'] = date("Y-m-d H:i:s",$item['c_time']);
        }
        return $this->ajaxReturn($ret);
    }
    public function getFiles()
    {
        $files = new MFiles();
        $user_id = $this->user;
        $ret = $files
            ->field("filelisturl,gitfile,file_title,c_time,tags")
            ->where("status",0)
            ->where("user_id",$user_id)
            ->select();
        $tag = new MTag();
        $tagsel = $tag->field("name,type,tag_id")->select();
        $tagArr = array();
        foreach ($tagsel as $tagitem){
            $tagArr[$tagitem['tag_id']] = $tagitem;
        }
        foreach ($ret as $key=>$item){
            $ret[$key]['c_time'] = date("Y-m-d H:i:s",$item['c_time']);
            $tags = explode(',',$item['tags']);
            foreach ($tags as $t){
                $ret[$key]['tagsel'][] = $tagArr[$t];
            }
        }
        return $this->ajaxReturn($ret);
    }
}