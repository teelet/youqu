<?php
/**
 * 单个社区首页
 * shaohua
 */

class Group_GrouphomeController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['g_g_id']     = (int) Comm_Context::param('g_g_id', 0);  //社区id
        $this->param['start']      = (int) Comm_Context::param('start', 0);
        $this->param['pagesize']   = (int) Comm_Context::param('pagesize', 10);
        //参数检查
        if($this->checkParam()){
            //获取社区列表
            $group_info = Group_GamegroupModel::getGroupInfo($this->param['g_g_id']);
            //获取用户数、帖子数
            $group_action_count = Group_GamegroupModel::getGameGroupActionCount($this->param['g_g_id']);
            //获取置顶帖子
            $blog_top = Blog_BlogModel::getBlogTop($this->param['g_g_id']);
            $uids = array(); 
            $top = array();
            if(! empty($blog_top)){ //获取帖子的card
                foreach ($blog_top as $value){
                    $card = Blog_BlogModel::getBlogCard($value['bid']);
                    $top[] = array_merge($value, $card);
                    $uids[] = $card['uid'];
                }
            }
            //获取社区最新成员列表
            $group_user = Group_GamegroupModel::getGroupNewUser($this->param['g_g_id']);
            $uids = array_merge($uids, $group_user);
            //获取用户信息
            $user_info = User_UserModel::getUserInfos($uids);
            
            $res = array(
                'groupinfo' => array_merge($group_info, $group_action_count),
                'topblog'   => $top,
                'userinfo'  => $user_info
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
