<?php
/**
 * 获取回贴信息
 * shaohua
 */

class Home_GetarticlereplyController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['aid']      = (int) Comm_Context::param('aid', 0);  //文章bid
        $this->param['a_c_id']   = (int) Comm_Context::param('a_c_id', 0);  //评论a_c_id
        $this->param['type']     = (int) Comm_Context::param('type', 0);  //1 一起返回回帖内容， 2 不返回
        $this->param['start']    = (int) Comm_Context::param('start', 0); //起始条
        $this->param['pagesize'] = (int) Comm_Context::param('pagesize', 10); //请求条数
        
        //参数检查
        if($this->checkParam()){
            $uids = array();
            if($this->param['type'] == 1){ //一起返回回帖内容
                $count = Article_ArticleModel::getCommentActionCount($this->param['a_c_id']);
                isset($count['reply_num']) ? $this->data['results']['comment']['reply_num'] = $count['reply_num'] : $this->data['results']['comment']['reply_num'] = 0;
                isset($count['favor_num']) ? $this->data['results']['comment']['favor_num'] = $count['favor_num'] : $this->data['results']['comment']['favor_num'] = 0;
                $comment = Article_ArticleModel::getCommentBaseInfo(array($this->param['a_c_id']));
                $this->data['results']['comment'] = array_merge( array_values($comment)[0], $this->data['results']['comment']);
                $uids[] = $this->data['results']['comment']['uid'];
            }
            $list = Article_ArticleModel::getReply($this->param['a_c_id'], $this->param['start'], $this->param['pagesize']);
            if(! empty($list)){
                foreach ($list as $value){
                    $this->data['results']['list'][] = $value;
                    $uids[] = $value['uid'];
                }
            }
            //获取点赞信息
            $favor = Article_ArticleModel::getFavor($this->param['a_c_id'], 2, 0, 10);
            
            if(! empty($favor)){
                    $this->data['results']['comment']['favor'] = $favor; 
            }
            
            //获取用户信息
            $userinfo = array();
            $uids = array_merge($uids, $favor);
            if(count($uids) > 0){
                $userinfo = User_UserModel::getUserInfos($uids);
            }
            $this->data['results']['userinfo'] = $userinfo;
            
            
            $this->format(0);
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['aid']) || empty($this->param['a_c_id']) || empty($this->param['type'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
