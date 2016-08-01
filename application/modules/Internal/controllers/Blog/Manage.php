<?php
/**
 * 管理员操作（置顶，加精，删除）
 * shaohua
 */

class Blog_ManageController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['bid']      = (int) Comm_Context::form('bid', 0);  //帖子bid
        $this->param['uid']      = (int) Comm_Context::form('uid', 0);
        $this->param['g_g_id']      = (int) Comm_Context::form('g_g_id', 0);
        $this->param['type']      = Comm_Context::form('type', 0); //1 置顶， 2 加精， 3 删除
        $this->param['atime']   = Comm_Context::form('atime', date('Y-m-d H:i:s'));
        $this->param['ctime']   = (int) Comm_Context::form('ctime', time());
        
        //参数检查
        if($this->checkParam()){
            $res = Blog_BlogModel::manage($this->param['uid'], $this->param['g_g_id'], $this->param['bid'], $this->param['type'], $this->param['atime'], $this->param['ctime']);
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
        if(empty($this->param['bid']) || empty($this->param['uid']) || empty($this->param['type']) || empty($this->param['g_g_id'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
