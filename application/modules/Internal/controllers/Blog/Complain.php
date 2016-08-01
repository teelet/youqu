<?php
/**
 * 举报
 * shaohua
 */

class Blog_ComplainController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['bid']      = (int) Comm_Context::form('bid', 0);  //帖子bid
        $this->param['uid']      = (int) Comm_Context::form('uid', 0);
        $this->param['content']      = Comm_Context::form('content', '');
        $this->param['atime']   = Comm_Context::form('atime', date('Y-m-d H:i:s'));
        $this->param['ctime']   = (int) Comm_Context::form('ctime', time());
        //参数检查
        if($this->checkParam()){
            $res = Blog_BlogModel::complain($this->param);
            if($res == 1){
                $this->format(0);
            }else{
                $this->format(3);
            }
            
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['bid']) || empty($this->param['uid'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
