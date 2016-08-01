<?php
/**
 * 加入社区
 * shaohua
 */

class Group_AddgroupController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['g_g_id']      = (int) Comm_Context::form('g_g_id', 0);  //社区id
        $this->param['uid']      = (int) Comm_Context::form('uid', 0);
        $this->param['atime']   = Comm_Context::form('atime', date('Y-m-d H:i:s'));
        $this->param['ctime']   = (int) Comm_Context::form('ctime', time());
        //参数检查
        if($this->checkParam()){
            $res = Group_GamegroupModel::addGameGroup($this->param);
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
        if(empty($this->param['g_g_id']) || empty($this->param['uid'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
