<?php
/**
 * 获取回贴信息
 * shaohua
 */

class Blog_GetreplyController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['uid']      = (int) Comm_Context::param('uid', 0);  //用户uid
        $this->param['bid']      = (int) Comm_Context::param('bid', 0);  //评论父亲id
        $this->param['start']    = (int) Comm_Context::param('start', 0); //起始条
        $this->param['pagesize'] = (int) Comm_Context::param('pagesize', 10); //请求条数
        
        //参数检查
        if($this->checkParam()){
            if(! empty($this->param['uid'])){ //查看指定uid的回复信息
                $this->data['results'] = Blog_ReplyModel::getBlogReply($this->param['bid'], $this->param['start'], $this->param['pagesize'] - 1, $this->param['uid']);
            }else{ //查看全部
                $this->data['results'] = Blog_ReplyModel::getBlogReply($this->param['bid'], $this->param['start'], $this->param['pagesize'] - 1);
            }
            $this->format(0);
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['bid'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
