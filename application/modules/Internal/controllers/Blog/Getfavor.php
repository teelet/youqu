<?php
/**
 * 获取点赞列表
 * shaohua
 */

class Blog_GetfavorController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['id']       = (int) Comm_Context::param('id', 0);  //id   bid或者b_c_id
        $this->param['type']     = (int) Comm_Context::param('type', 0); // 1 帖子点赞信息，2 回帖点赞信息
        $this->param['start']    = (int) Comm_Context::param('start', 0);
        $this->param['pagesize'] = (int) Comm_Context::param('pagesize', 10);
        //参数检查
        if($this->checkParam()){
            if($this->param['type'] == 1){
                $uids = Blog_ReplyModel::getFavor($this->param['id'], 1, $this->param['start'], $this->param['pagesize'] - 1);
            }else{ //给回帖点赞
                $uids = Blog_ReplyModel::getFavor($this->param['id'], 2, $this->param['start'], $this->param['pagesize'] - 1);
            }
            //获取用户信息
            $userinfo = array();
            if(count($uids) > 0){
                $userinfo = User_UserModel::getUserInfos(array_unique($uids));
            }
            
            if($userinfo){
                $this->data['results'] = $userinfo;
                $this->format(0);
            }else{
                $this->format(3);
            }
            
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['type']) || empty($this->param['id'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
