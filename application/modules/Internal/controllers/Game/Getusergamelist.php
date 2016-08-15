<?php
/**
 * 个人游戏列表
 * shaohua
 */

class Game_GetusergamelistController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['uid'] = (int) Comm_Context::param('uid', 0);  //用户uid
        //参数检查
        if($this->checkParam()){
            //获取个人游戏列表
            $gamelist = Game_GameModel::getGameListByUid($this->param['uid']);
            $games = array();
            if($gamelist){
                $items = explode(',', $gamelist);
                $all_gamelist = Game_GameModel::getGameList();
                foreach ($items as $gid){
                    $games[] = $all_gamelist[$gid];
                }
            }else{
                $games = array();
            }

            //操作成功
            $this->format(0);
            $this->data['results'] = $games;
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