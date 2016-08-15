<?php
/**
 * 游戏搜索
 * shaohua
 */

class Game_GamesearchController extends AbstractController {

    public function indexAction() {
        //获取参数
        $this->param['keywords'] = Comm_Context::param('keywords', '');
        //参数检查
        if($this->checkParam()){
            $res = Game_GameModel::search($this->param['keywords']);

            //操作成功
            $this->format(0);
            $this->data['results'] = $res;
        }
        $this->jsonResult($this->data);
        return $this->end();
    }

    public function checkParam(){
        if(empty($this->param['keywords'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }

}