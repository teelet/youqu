<?php
/**
 * 文章详细
 * shaohua
 */

class Home_ArticledetailController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['aid'] = (int) Comm_Context::param('aid', 0);  //文章id
        $this->param['uid'] = (int) Comm_Context::param('uid', 0);  //阅读者id
        //参数检查
        if($this->checkParam()){
            //获取文章详细信息
            $article = Article_ArticleModel::getArticleDetail($this->param['aid']);
            if($article){
                $this->format(0);
                $this->data['results'] = $article;
            }
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
