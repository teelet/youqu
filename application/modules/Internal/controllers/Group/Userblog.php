<?php
/**
 * 用户社区帖子列表
 * shaohua
 */

class Group_UserblogController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['g_g_id']     = (int) Comm_Context::param('g_g_id', 0);  //社区id
        $this->param['uid']       = (int) Comm_Context::param('uid', 0);  
        $this->param['start']      = (int) Comm_Context::param('start', 0);
        $this->param['pagesize']   = (int) Comm_Context::param('pagesize', 10);
        //参数检查
        if($this->checkParam()){
            //获取帖子card
            $blog_list = Blog_BlogModel::getUserBlog($this->param['g_g_id'], $this->param['uid'], $this->param['start'], $this->param['pagesize']);
            //获取详情
            $res = array();
            if(!empty($blog_list)){
                foreach ($blog_list as $bid){
                    $one = Blog_BlogModel::getBlogCard($bid);
                    if($one){
                        $res[] = $one;
                    }
                }
            }
            if($res){
                $this->data['results'] = $res;
                $this->format(0);
            }else{
                $this->format(3);
            }
            
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['g_g_id']) || empty($this->param['uid'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
