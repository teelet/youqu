<?php
/**
 * 获取推荐游戏列表
 * shaohua
 */

class Game_RecommendController extends AbstractController {

    public function indexAction() {
        //参数检查
        if($this->checkParam()){
            //推荐列表
            $games = Game_GameModel::getRecommend();

            if($games){
                $this->format(0);
                $this->data['results'] = $games;
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