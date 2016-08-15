<?php
/**
 * 添加游戏
 * shaohua
 */

class Game_GameaddController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['type']  = (int) Comm_Context::form('type', 0);  //1 来自引导推荐， 2 来自选择游戏
        $this->param['uid']   = (int) Comm_Context::form('uid', 0);  //用户uid
        $this->param['gids']  = Comm_Context::form('gids', 0);  //游戏id  多个用 逗号 隔开
        $this->param['atime'] = Comm_Context::form('atime', date('Y-m-d H:i:s'));
        $this->param['ctime'] = (int) Comm_Context::form('ctime', time());
        //参数检查
        if($this->checkParam()){
            //获取个人游戏列表
            $res = Game_GameModel::userGameAdd($this->param['type'], $this->param['uid'], $this->param['gids'],$this->param['atime'], $this->param['ctime']);

            if($res == -1){
                //重复添加
                $this->format(4);
            }elseif($res == 1){
                //操作成功
                $this->format(0);
            }else{
                //网络故障
                $this->format(3);
            }
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['uid']) || empty($this->param['type']) || empty($this->param['gids'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }

}