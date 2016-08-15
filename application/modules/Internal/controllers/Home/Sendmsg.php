<?php
/**
 * 发送手机短信息
 * shaohua
 */

class Home_SendmsgController extends AbstractController {

    public function indexAction() {
        //获取参数
        $this->param['uid']      = (int) Comm_Context::form('uid', 0);  //uid
        $this->param['tel']      = (int) Comm_Context::form('tel', 0);  //手机号
        //参数检查
        if($this->checkParam()){
            //推荐列表
            $res = Msg_MsgModel::sendMsg($this->param['uid'], $this->param['tel']);

            if($res){
                $this->format(0);
            }else{
                $this->format(3);
            }

        }
        $this->jsonResult($this->data);
        return $this->end();
    }

    public function checkParam(){
        if(empty($this->param['uid']) || empty($this->param['tel'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }

}