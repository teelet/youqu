<?php
/**
 * 获取文章图片列表
 * shaohua
 */

class Home_GetarticleimagesController extends AbstractController {

    public function indexAction() {
        //获取参数
        $this->param['aid']      = (int) Comm_Context::param('aid', 0);  //文章id
        $this->param['start']    = (int) Comm_Context::param('start', 0);
        $this->param['pagesize'] = (int) Comm_Context::param('pagesize', 10);

        //参数检查
        if($this->checkParam()){
            //获取图片
            $images = Article_ArticleModel::getArticleImg($this->param['aid'], $this->param['start'], $this->param['pagesize']);

            if($images){
                $this->format(0);
                $this->data['results'] = $images;
            }else{
                $this->format(3);
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