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
            $count = Blog_BlogModel::getBlogActionCount($this->param['bid']);
            $blog['reply_num'] = $count['reply_num'] ? $count['reply_num'] : 0;
            //获取帖子的正文图
            $blog['images'] = array();
            if($blog['pic_num'] > 0){
                $image_list = Blog_BlogModel::getBlogImage($this->param['bid']);
                if(! empty($image_list)){
                    foreach ($image_list as $value){
                        $blog['images'][$value['b_i_id']] = $value['url_2'];
                    }
                }
            }
            //获取帖子是否被管理员修改
            $log = Blog_BlogModel::getBlogManagerLog($this->param['bid']);
            if(count($log) > 0){
                $log_last = explode('_', $log[0]);
                $username = User_UserModel::getUserInfo($log_last[1])['nickname'];
                $atime = $log_last[3];
                $action = '修改';
                //1 置顶， 2 加精， 3 删除，4 修改
                switch ($log_last[2]){
                    case 1 : $action = '置顶';
                        break;
                    case 2 : $action = '加精';
                        break;
                    case 3 : $action = '删除';
                        break;
                    case 4 : $action = '修改';
                        break;
                }
                $msg = sprintf("管理员：%s 于 %s %s过", $username, $atime, $action);
                $blog['action_log'] = $msg;
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
