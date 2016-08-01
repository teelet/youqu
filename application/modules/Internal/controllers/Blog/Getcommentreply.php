<?php
/**
 * 获取回贴信息
 * shaohua
 */

class Blog_GetCommentreplyController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['bid']      = (int) Comm_Context::param('bid', 0);  //帖子bid
        $this->param['b_c_id']   = (int) Comm_Context::param('b_c_id', 0);  //回帖b_c_id
        $this->param['type']     = (int) Comm_Context::param('type', 0);  //1 一起返回回帖内容， 2 不返回
        $this->param['start']    = (int) Comm_Context::param('start', 0); //起始条
        $this->param['pagesize'] = (int) Comm_Context::param('pagesize', 10); //请求条数
        
        //参数检查
        if($this->checkParam()){
            $uids = array();
            if($this->param['type'] == 1){ //一起返回回帖内容
                $count = Blog_BlogModel::getBlogCommentActionCount($this->param['b_c_id']);
                isset($count['reply_num']) ? $this->data['results']['comment']['reply_num'] = $count['reply_num'] : $this->data['results']['comment']['reply_num'] = 0;
                isset($count['favor_num']) ? $this->data['results']['comment']['favor_num'] = $count['favor_num'] : $this->data['results']['comment']['favor_num'] = 0;
                $comment = Blog_ReplyModel::getBlogCommentBaseInfo(array($this->param['b_c_id']));
                $this->data['results']['comment'] = array_merge( array_values($comment)[0], $this->data['results']['comment']);
                //获取帖子图片
                if($this->data['results']['comment']['pic_num'] > 0){
                    $images = Blog_ReplyModel::getBlogCommentImage($this->data['results']['comment']['b_c_id']);
                    if(! empty($images)){
                        foreach ($images as $image){
                            $this->data['results']['comment']['images'][] = $image['url_2'];
                        }
                    }
                }
                
                $uids[] = $this->data['results']['comment']['uid'];
            }
            $list = Blog_ReplyModel::getBlogCommentReply($this->param['b_c_id'], $this->param['start'], $this->param['pagesize'] - 1);
            if(! empty($list)){
                foreach ($list as $value){
                    $this->data['results']['list'][] = $value;
                    $uids[] = $value['uid'];
                }
            }
            
            //获取点赞信息
            $favor = Blog_ReplyModel::getFavor($this->param['b_c_id'], 2, 0, 10);
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
        if(empty($this->param['bid']) || empty($this->param['type'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
