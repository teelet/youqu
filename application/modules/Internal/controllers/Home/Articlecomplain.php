<?php
/**
 * 举报
 * shaohua
 */

class Home_ArticlecomplainController extends AbstractController {

    public function indexAction() {
        //获取参数
        $this->param['aid']      = (int) Comm_Context::form('aid', 0);  //文章aid
        $this->param['uid']      = (int) Comm_Context::form('uid', 0);
        $this->param['content']  = Comm_Context::form('content', '');
        $this->param['atime']    = Comm_Context::form('atime', date('Y-m-d H:i:s'));
        $this->param['ctime']    = (int) Comm_Context::form('ctime', time());
        //参数检查
        if($this->checkParam()){
            $res = Article_ArticleModel::complain($this->param);
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
        if(empty($this->param['aid']) || empty($this->param['uid'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }

}
