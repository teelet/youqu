<?php
/**
 * 用户信息
 * shaohua
 */

class Me_UserinfoController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['uid'] = (int) Comm_Context::param('uid', 0);  //用户uid
        //参数检查
        if($this->checkParam()){
            //获取用户信息
            $userInfo = User_UserModel::getUserInfo($this->param['uid']);
            if(! $userInfo){
                $this->format(3);
            }
            //操作成功
            $this->format(0);
            $this->data['results'] = $userInfo;
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