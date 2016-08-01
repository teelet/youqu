<?php
/**
 * 帖子正文
 * shaohua
 */

class Blog_DetailController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['uid']  = (int) Comm_Context::param('uid', 0);  //查看这uid
        $this->param['bid']  = (int) Comm_Context::param('bid', 0);  //帖子bid
        //参数检查
        if($this->checkParam()){
            $blog = Blog_BlogModel::getBlogDetail($this->param['bid']);
            if(! empty($blog)){
                $this->format(3);
            }
            //获取帖主信息
            $userinfo = User_UserModel::getUserInfo($blog['uid']);
            $blog['userinfo'] = array(
                'uid' => $userinfo['uid'],
                'name' => $userinfo['name'],
                'nickname' => $userinfo['nickname'],
                'url' => $userinfo['url'],
                'summary' => $userinfo['summary']
            );
            //获取回帖数
            $blog['reply_num'] = Blog_BlogModel::getBlogActionCount($this->param['bid'])['reply_num'];
            //获取帖子的正文图
            $blog['images'] = array();
            $image_list = Blog_BlogModel::getBlogImage($this->param['bid']);
            if(! empty($image_list)){
                foreach ($image_list as $value){
                    $blog['images'][] = $value['url_2'];
                }
            }
            //操作成功
            $this->format(0);
            $this->data['results'] = $blog;
        } 
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['uid']) || empty($this->param['bid'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
   
}
