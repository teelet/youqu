<?php
/**
 * 文章评论列表
 * shaohua
 */

class Home_GetarticlecommentController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['uid']      = (int) Comm_Context::param('uid', 0);
        $this->param['aid']      = (int) Comm_Context::param('aid', 0);  //文章id
        $this->param['start']    = (int) Comm_Context::param('start', 0);
        $this->param['pagesize']    = (int) Comm_Context::param('pagesize', 10);
        //参数检查
        if($this->checkParam()){
            //获取评论列表
            $res = Article_ArticleModel::getArticleComment($this->param['aid'], $this->param['start'], $this->param['pagesize']);
            //获取用户信息
            $uids = array();
            if($res){
                foreach ($res as $value){
                    $uids[] = $value['uid'];
                    if($value['reply']){
                        foreach ($value['reply'] as $v){
                            $uids[] = $v['uid'];
                            $uids[] = $v['touid'];
                        }
                    }
                    $this->data['results']['list'][] = $value;
                }
            }
            $uids = array_unique($uids);
            if(count($uids) > 0){
                $user_info = User_UserModel::getUserInfos($uids);
                $this->data['results']['userinfo'] = $user_info;
            }
               
                
            $this->format(0);
            
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['aid'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
