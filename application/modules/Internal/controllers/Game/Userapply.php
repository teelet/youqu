<?php
/**
 * 用户申请新游戏
 * shaohua
 */

class Game_UserapplyController extends AbstractController {

    public function indexAction() {
        //获取参数
        $this->param['uid']       = Comm_Context::form('uid', 0);
        $this->param['checkCode'] = Comm_Context::form('checkCode', '');
        $this->param['gameName']  = Comm_Context::form('gameName', '');
        $this->param['atime']    = Comm_Context::form('atime', date('Y-m-d H:i:s'));
        $this->param['ctime']    = (int) Comm_Context::form('ctime', time());

        //参数检查
        if($this->checkParam()){
            //验证码是否过期
            $code_status = Msg_MsgModel::checkCode($this->param['uid'], $this->param['checkCode']);
            if($code_status == 1){
                //添加游戏
                $res = Game_GameModel::userApply($this->param);
                if($res){
                    $this->format(0);
                }else{
                    $this->format(3);
                }

            }elseif($code_status == -1){ //验证码过期
                $this->format(7);
            }elseif($code_status == 0){ //验证码错误
                $this->format(6);
            }else{
                $this->format(3);
            }

        }
        $this->jsonResult($this->data);
        return $this->end();
    }

    public function checkParam(){
        if(empty($this->param['uid']) || empty($this->param['checkCode']) || empty($this->param['gameName'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }

}