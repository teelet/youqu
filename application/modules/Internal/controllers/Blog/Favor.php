<?php
/**
 * 获取回贴信息
 * shaohua
 */

class Blog_FavorController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['bid']      = (int) Comm_Context::form('bid', 0);  //帖子bid
        $this->param['b_c_id']   = (int) Comm_Context::form('b_c_id', 0);  //回帖b_c_id
        $this->param['uid']      = (int) Comm_Context::form('uid', 0);
        $this->param['type']     = (int) Comm_Context::form('type', 0);  //1 一起返回回帖内容， 2 不返回
        $this->param['atime']   = Comm_Context::form('atime', date('Y-m-d H:i:s'));
        $this->param['ctime']   = (int) Comm_Context::form('ctime', time());
        //参数检查
        if($this->checkParam()){
            if($this->param['type'] == 1){ //给帖子点赞
                $res = Blog_ReplyModel::favor($this->param);
            }else{ //给回帖点赞
                $res = Blog_ReplyModel::favor($this->param);
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
