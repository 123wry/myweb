<?php
namespace app\index\controller;

use app\index\model\MArticle;
use app\index\model\MAtag;
use app\index\model\MFiles;
use app\index\model\MMenu;
use app\index\model\MTag;
use think\File;

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
            "user_id"=>$this->user
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
        $filelistbase = base64_decode($filelist);dump($filelistbase);
        $start = strpos($filelist,'/');
        $end = strpos($filelist,';');
        $tmp = substr($filelistbase,$start,$end);
        $filelist_name = rand(0,999999).'_'.time().$tmp;
        $filelist_url = $file_.$filelist_name;
        file_put_contents($filelist_url,$filelistbase);

        $files = new MFiles();
        $files->data([
            "file_title"=>$input,
            "filelisturl"=>$filelist_url,
            "gitfile"=>$gitinput,
            "user_id"=>$this->user
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

}