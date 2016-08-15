<?php
/**
 * 发表文章评论 + 回复
 * shaohua
 */

class Home_PostarticlereplyController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['type']    = (int) Comm_Context::form('type', 0);  //1 评论 2 回复
        $this->param['aid']     = (int) Comm_Context::form('aid', 0);  //文章id
        $this->param['uid']     = (int) Comm_Context::form('uid', 0);  //评论者或回复者uid
        $this->param['content'] = Comm_Context::form('content', '');  //评论或回复内容
        $this->param['atime'] = Comm_Context::form('atime', date('Y-m-d H:i:s'));
        $this->param['ctime'] = Comm_Context::form('ctime', time());
        //参数检查
        if($this->checkParam()){
            if($this->param['type'] == 1){ // 评论
                $res = Article_ArticleModel::setArticleComment($this->param);
            }elseif($this->param['type'] == 2){ // 回复
                $this->param['touid']    = (int) Comm_Context::form('touid', 0);
                $this->param['a_c_id']   = (int) Comm_Context::form('a_c_id', 0);
                $this->param['a_c_r_id'] = (int) Comm_Context::form('a_c_r_id', 0); //当回复评论是为0， 回复 回复时有用
                $res = Article_ArticleModel::setArticleCommentReply($this->param);
            }
            if(!$res){
                $this->format(3);
            }
            $this->format(0);
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['aid']) || empty($this->param['uid']) || empty($this->param['type']) || empty($this->param['content'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
