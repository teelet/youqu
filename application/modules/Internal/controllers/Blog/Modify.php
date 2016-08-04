<?php
/**
 * 管理员操作（置顶，加精，删除）
 * shaohua
 */

class Blog_ModifyController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['bid']       = (int) Comm_Context::form('bid', 0);  //帖子bid
        $this->param['uid']       = (int) Comm_Context::form('uid', 0);
        $this->param['type']      = (int)Comm_Context::form('type', 0); //1 更新标题， 2 正文，3删除图片，4更新图片，5上传图片
        $this->param['content']   = Comm_Context::form('content', '');
        $this->param['imgId_del'] = Comm_Context::form('imgId_del', '');
        //参数检查
        if($this->checkParam()){
            if($this->param['type'] == 1 || $this->param['type'] == 2){
                $res = Blog_BlogModel::modify($this->param['uid'], $this->param['bid'], $this->param['type'], $this->param['content']);
            }elseif($this->param['type'] == 3){
                $res = Blog_BlogModel::delBlogImg($this->param['bid'], array_unique(explode('_', $this->param['imgId_del'])));
            }
            
            if($res == 1){
                $this->format(0);
            }else{
                $this->format(3);
            }
            
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['bid']) || empty($this->param['uid']) || empty($this->param['type'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
