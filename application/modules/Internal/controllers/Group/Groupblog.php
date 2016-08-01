<?php
/**
 * 单个社区帖子card列表
 * shaohua
 */

class Group_GroupblogController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['g_g_id']     = (int) Comm_Context::param('g_g_id', 0);  //社区id
        $this->param['type']       = (int) Comm_Context::param('type', 0);  //0 全部， 1 精华帖
        $this->param['start']      = (int) Comm_Context::param('start', 0);
        $this->param['pagesize']   = (int) Comm_Context::param('pagesize', 10);
        //参数检查
        if($this->checkParam()){
            //获取帖子card
            $blog_list = Blog_BlogModel::getGroupBlog($this->param['g_g_id'], $this->param['start'], $this->param['pagesize'] - 1, $this->param['type']);
            $list = array();
            $uids = array();
            if($blog_list){
                foreach ($blog_list as $value){
                    $card = Blog_BlogModel::getBlogDetail($value);
                    $action_count = Blog_BlogModel::getBlogActionCount($value);
                    if($action_count){
                        $card = array_merge($card, $action_count);
                    }
                    //获取帖子的正文图
                    $images = array();
                    $image_list = Blog_BlogModel::getBlogImage($value);
                    if(! empty($image_list)){
                        foreach ($image_list as $value){
                            $images[] = $value['url_2'];
                        }
                        $card['images'] = $images;
                    }
                    
                    $list[] = $card;
                    $uids[] = $card['uid'];
                }
            }
            //获取用户信息
            $user_info = User_UserModel::getUserInfos(array_unique($uids));
            $res = array(
                'bloglist' => $list,
                'userinfo' => $user_info
            );
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
        if(empty($this->param['g_g_id'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
