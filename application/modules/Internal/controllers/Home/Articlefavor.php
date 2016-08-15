<?php
/**
 * 点赞
 * shaohua
 */

class Home_ArticlefavorController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['aid']      = (int) Comm_Context::form('aid', 0);  //文章id
        $this->param['a_c_id']   = (int) Comm_Context::form('a_c_id', 0);  //评论id
        $this->param['uid']      = (int) Comm_Context::form('uid', 0);
        $this->param['type']     = (int) Comm_Context::form('type', 0);  //1 文章点赞， 2 评论点赞
        $this->param['atime']    = Comm_Context::form('atime', date('Y-m-d H:i:s'));
        $this->param['ctime']    = (int) Comm_Context::form('ctime', time());
        //参数检查
        if($this->checkParam()){
            if($this->param['type'] == 1){ //给文章点赞
                $res = Article_ArticleModel::favor($this->param);
            }else{ //给评论点赞
                $res = Article_ArticleModel::favor($this->param);
            }
            
            if($res == 1){
                $this->format(0);
            }elseif($res == -1){ //重复点赞
                $this->format(4);
            }else{
                $this->format(3);
            }
            
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['type']) || empty($this->param['uid'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
