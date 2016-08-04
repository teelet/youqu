<?php

/**
 * @author shaohua5
 * api开发的curl调试工具
 * request: http://i.domain/curl/get
 */

class CurlController extends AbstractController {

    public function getAction(){
        
        
        //帖子正文
        $data = array(
            'uid'  => 1, //阅读者uid
            'buid' => 1, //帖子uid
            'bid'  => 10  //帖子bid              
        );
        $url = "http://i.youqu.intra.weibo.com/blog_detail";
        
        
        /*
        //用户信息
        $data = array(
            'uid' => 1,
        );
        $url = "http://i.youqu.intra.weibo.com/me_userinfo";
        */
        
        /*
        //获取回帖信息
        $data = array(
            'bid' => 1,   
            'uid' => 0,  //查看指定uid的回帖信息 默认为 0 
            'start' => 0, //起始
            'pagesize' => 10, //条数 
        );
        $url = "http://i.youqu.intra.weibo.com/blog_getreply";
        */
        
        /*
        //获取回复信息
        $data = array(
            'bid' => 1,
            'b_c_id' => 1, //回帖的id
            'type'   => 1, //1 一起返回回帖内容， 2 不返回
            'start' => 0, //起始
            'pagesize' => 10, //条数
        );
        $url = "http://i.youqu.intra.weibo.com/blog_getcommentreply";
        */
        /*
        //获取社区列表
        $data = array(
            'uid' => 1,
            'g_g_id' => 1, 
        );
        $url = "http://i.youqu.intra.weibo.com/group_grouplist";
        */
        /*
        //获取社区列表
        $data = array(
            'gid' => 1
        );
        $url = "http://i.youqu.intra.weibo.com/group_grouplistbygid";
        */
        
        /*
        //获取社区详细
        $data = array(
            'g_g_id' => 1
        );
        $url = "http://i.youqu.intra.weibo.com/group_grouphome";
        */
        /*
        //获取单个社区下的blog列表
        $data = array(
            'g_g_id' => 1,
            'type' => 0
        );
        $url = "http://i.youqu.intra.weibo.com/group_groupblog";
        */
        /*
        //社区首页
        $data = array(
            'start' => 0,
            'pagesize' => 10,
        ); 
        $url = "http://i.youqu.intra.weibo.com/group_home";
        */
        /*
        //推荐社区
        $data = array(
            'uid' => 1
        );
        $url = "http://i.youqu.intra.weibo.com/group_recommendgroup";
        */
        /*
        //获取点赞信息
        $data = array(
            'id' => 1,
            'type' => 2,
            'start' => 0,
            'pagesize' => 10
        );
        $url = "http://i.youqu.intra.weibo.com/blog_getfavor"; 
        */
        /*
        //用户帖子列表
        $data = array(
            'g_g_id' => 1,
            'uid' => 1,
            'start' => 0,
            'pagesize' => 10
        );
        $url = "http://i.youqu.intra.weibo.com/group_userblog";
        */
        
        $method = 'GET';
        $http = new Comm_HttpRequest();
		$http->url = $url;
		$http->set_method($method);
        foreach ($data as $k => $v){
            $http->add_query_field($k, $v);
        }
		$http->set_timeout(5000);
		$http->set_connect_timeout(5000);
		$http->send();
		$ret = $http->get_response_content();
		echo $ret;
        exit;
    }

    public function postAction(){
        /*
        //发帖
        $data = array(
            'uid'       => 1,
            'g_g_id'    => 1, //英雄联盟
            'title'     => '我是标题',
            'content'   => '我是内容',
            'address'   => '北京',
            'pic_num'   => '2',
            'pic_0'     => base64_encode(file_get_contents('http://img0w.pconline.com.cn/pconline/1401/15/4172339_touxiang/23.jpg')),
            'pic_1'     => base64_encode(file_get_contents('http://e.hiphotos.baidu.com/zhidao/wh%3D450%2C600/sign=972b6623ccfc1e17fdea84357fa0da35/94cad1c8a786c917abbc830cca3d70cf3bc7574c.jpg')),
            'pic_ext_0' => 'jpg',
            'pic_ext_1' => 'jpg',
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/blog_postblog";
        */
        
        /*
        //回帖+回复
        $data = array(
            'type' => 2, //1 回帖， 2 回复
            'uid'  => 2, //评论者uid
            'buid' => 1, //楼主uid
            'touid' => 1,//被ping者的uid
            'bid'  => 1,
            'content'   => '回复回复回复',
            'b_c_id' => 116,  //被回复的回帖b_c_id
            'b_c_c_id' => 0, //被回复的 回复b_c_cid
            //'pic_num'   => 2,
            //'pic_0'     => base64_encode(file_get_contents('http://img0.pconline.com.cn/pconline/1508/13/6824500_006_thumb.jpg')),
            //'pic_1'     => base64_encode(file_get_contents('http://img0w.pconline.com.cn/pconline/1312/26/4067557_26-080440_831.jpg')),
            //'pic_ext_0' => 'jpg',
            //'pic_ext_1' => 'jpg',
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/blog_postblogreply";
        */
        
        /*
        //点赞
        $data = array(
            'type' => 2, //1 给帖子点赞 ，2 给回帖点赞
            'bid'  => 1, 
            'b_c_id' => 1,
            'uid' => 5,
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/blog_favor";
        */
        /*
        //加入社区
        $data = array(
            'g_g_id' => 1,
            'uid' => 7,
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/group_addgroup";
        */
        /*
        //签到
        $data = array(
            'g_g_id' => 1,
            'uid' => 1,
            'atime'     => date('Ymd'),
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/group_sign";
        */
        
        /*
        //举报
        $data = array(
            'uid' => 1,
            'bid' => 2,
            'content' => '举报举报举报举报举报举报',
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/blog_complain";
        */
        /*
        //置顶,加精，删除
        $data = array(
            'uid' => 1,
            'bid' => 5,
            'g_g_id' => 1,
            'type' => 3,//1 置顶， 2 加精， 3 删除
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/blog_manage";
        */
        
        //更新帖子
        $data = array(
            'uid' => 1,
            'bid' => 4,
            'type' => 2,//1 更新标题， 2 更新正文，3删除图片，4更新图片，5上传图片
            'content' => "change content sss",
            'imgId_del' => '7_8', //多个图片id用 ‘_’ 下划线分割
        );
        $url = "http://i.youqu.intra.weibo.com/blog_modify";
        
        
        $method = 'POST';
        $http = new Comm_HttpRequest();
        $http->url = $url;
        $http->set_method($method);
        foreach ($data as $k => $v){
            $http->add_post_field($k, $v);
        }
        $http->set_timeout(5000);
        $http->set_connect_timeout(5000);
        $http->send();
        $ret = $http->get_response_content();
        echo $ret;
        exit;
    }
    
}