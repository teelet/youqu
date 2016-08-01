<?php
/**
 * 指定游戏下的社区列表
 * shaohua
 */

class Group_GrouplistbygidController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['gid']      = (int) Comm_Context::param('gid', 0);  //游戏id
        $this->param['start']    = (int) Comm_Context::param('start', 10);
        $this->param['pagesize'] = (int) Comm_Context::param('pagesize', 10);
        //参数检查
        if($this->checkParam()){
            //获取社区列表
            $res = Group_GamegroupModel::getGroupListByGid($this->param['gid'], $this->param['start'], $this->param['pagesize'] - 1);
            
            if($res){
                $this->data['results']['grouplist'] = $res;
                $this->format(0);
            }else{
                $this->format(3);
            }
            
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['gid'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
