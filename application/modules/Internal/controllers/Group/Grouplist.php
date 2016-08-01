<?php
/**
 * 社区列表
 * shaohua
 */

class Group_GrouplistController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['uid']      = (int) Comm_Context::param('uid', 0);
        $this->param['g_g_id']      = (int) Comm_Context::param('g_g_id', 0);  //社区id
        //参数检查
        if($this->checkParam()){
            //获取社区列表
            $res = Group_GamegroupModel::getGroupList($this->param['g_g_id']);
            //获取用户已加入的社区列表
            $user_groups = Group_GamegroupModel::getUserGroups($this->param['uid']);
            if($user_groups){
                $res['usergrouplist'] = $user_groups;
            }
            if($res){
                $this->data['results'] = $res;
                $this->format(0);
            }else{
                $this->format(3);
            }
            
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['uid'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
