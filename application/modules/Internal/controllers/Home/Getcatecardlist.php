<?php
/**
 * 获取各个分类频道的cardlist
 */

class Home_GetcatecardlistController extends AbstractController {

    public function indexAction() {
        //获取参数
        $this->param['cateid']   = Comm_Context::param('cateid', '1001');  //分类频道号  默认为"推荐"频道
        $this->param['uid']      = (int) Comm_Context::param('uid', 0);  //用户id
        $this->param['gid']      = (int) Comm_Context::param('gid', 0);  //游戏id
        $this->param['start']    = (int) Comm_Context::param('start', 0);
        $this->param['pagesize'] = (int) Comm_Context::param('pagesize', 10);

        //参数检查
        if($this->checkParam()){
            //假定数据
            $all_list = array(1,2,3,4,5,6,7,8,9);
            $current_list = array_slice($all_list, $this->param['start'], $this->param['pagesize']);
            //获取card信息
            $cards = Article_ArticleModel::getCardList($current_list);
            if($cards){
                $this->format(0);
                $this->data['results'] = $cards;
            }else{
                $this->format(3);
            }

        }
        $this->jsonResult($this->data);
        return $this->end();
    }

    public function checkParam(){
        if(empty($this->param['cateid'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }

}