<?php
/**
 * 获取游戏列表
 * shaohua
 */

class Game_GetgamelistController extends AbstractController {
    
    public function indexAction() {
        //参数检查
        if($this->checkParam()){
            $gamelist = array();
            $list = Game_GameModel::getGameList();
            if($list){
                //按游戏分类 格式化返回
                foreach ($list as $game){
                    $gamelist[$game['g_t_id']]['g_t_id'] = $game['g_t_id'];
                    $gamelist[$game['g_t_id']]['g_t_name'] = $game['g_t_name'];
                    $gamelist[$game['g_t_id']]['list'][] = $game;
                }
                ksort($gamelist);
                $this->format(0);
                $this->data['results'] = $gamelist;
            }else{
                $this->format(3);
            }
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        return true;
    }
    
}
