<?php

/**
 * @author shaohua5
 * api开发的curl调试工具
 * request: http://i.domain/curl/get
 */

class CurlController extends AbstractController {

    public function getAction(){
        /*
        //帖子正文
        $data = array(
            'uid'  => 1, //阅读者uid
            'buid' => 1, //帖子uid
            'bid'  => 10  //帖子bid              
        );
        $url = "http://i.youqu.intra.weibo.com/blog_detail";
        */
        
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
            'pagesize' => 50, //条数 
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
        //获取单个社区列表
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
        /*
        //文章正文
        $data = array(
            'uid'  => 1, //阅读者uid
            'aid'  => 1  //文章id
        );
        $url = "http://i.youqu.intra.weibo.com/home_articledetail";
        */
        /*
        //获取文章评论
        $data = array(
            'uid'  => 1, //阅读者uid
            'aid'  => 1,  //文章id
            'start' => 0, //起始
            'pagesize' => 4, //条数
        );
        $url = "http://i.youqu.intra.weibo.com/home_getarticlecomment";
        */
        /*
        //获取单个评论的回复
        $data = array(
            'aid' => 1,
            'a_c_id' => 1, //回帖的id
            'type'   => 1, //1 一起返回回帖内容， 2 不返回
            'start' => 0, //起始
            'pagesize' => 10, //条数
        );
        $url = "http://i.youqu.intra.weibo.com/home_getarticlereply";
        */
        /*
        //获取所有游戏列表
        $data = array();
        $url = "http://i.youqu.intra.weibo.com/game_getgamelist";
        */
        /*
        //获取个人游戏列表
        $data = array(
            'uid' => 1
        );
        $url = "http://i.youqu.intra.weibo.com/game_getusergamelist";
        */
        /*

        //游戏搜索
        $data = array(
            'keywords' => '英雄'
        );
        $url = "http://i.youqu.intra.weibo.com/game_gamesearch";
        */
        /*
        //获取文章点赞
        $data = array(
            'id' => 1,
            'type' => 2,
            'start' => 0,
            'pagesize' => 10
        );
        $url = "http://i.youqu.intra.weibo.com/home_getarticlefavor";
        */
        /*
        //获取顶导
        $data = array();
        $url = "http://i.youqu.intra.weibo.com/home_getnav";
        */

        /*
        //获取分类数据
        $data = array(

        );
        $url = "http://i.youqu.intra.weibo.com/home_getnav";
        */
        /*
        //获取各个分类频道的cardlist
        $data = array(
            'cateid' => '1001',
            'uid' => 0,
            'gid' => 0,
            'start' => 0,
            'pagesize' => 5
        );
        $url = "http://i.youqu.intra.weibo.com/home_getcatecardlist";
        */

        /*
        //获取文章图片列表
        $data = array(
            'aid' => 2,
            'start' => 0,
            'pagesize' => 2
        );
        $url = "http://i.youqu.intra.weibo.com/home_getarticleimages";
        */


        //游戏推荐
        $data = array(
        );
        $url = "http://i.youqu.intra.weibo.com/game_recommend";





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
            'title'     => '我是标题newnew',
            'content'   => '我是内容newnew',
            'address'   => '北京',
            'pic_num'   => '2',
            'pic_name_1' => 'a_2.jpg',
            'pic_name_2' => 'b_2.jpg',
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/blog_postblog";
        */
        
        /*
        //回帖+回复
        $data = array(
            'type' => 1, //1 回帖， 2 回复
            'uid'  => 2, //评论者uid
            'buid' => 1, //楼主uid
            'touid' => 1,//被ping者的uid
            'bid'  => 1,
            'content'   => '回帖回帖new',
            'b_c_id' => 1,  //被回复的回帖b_c_id
             //'b_c_c_id' => 11, //被回复的 回复b_c_cid
            'pic_num'   => '2',
            'pic_name_1' => 'a_2.jpg',
            'pic_name_2' => 'b_2.jpg',
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
        //举报 帖子
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
            'is_own' => 0,// 1 用户自己，0否
            'bid' => 165,
            'g_g_id' => 1,
            'type' => 2,//1 置顶， 2 加精， 3 删除
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/blog_manage";
        */
        /*
        //更新帖子
        $data = array(
            'uid' => 1,
            'bid' => 10, 
            'g_g_id' => 1,
            'type' => 5,//1 更新标题， 2 更新正文，3删除图片，4更新图片，5上传图片
            //type 1,2
            'content' => "change content .....",
            //type 3
            'imgId_del' => '7_8', //多个图片id用 ‘_’ 下划线分割
            //type 4
            'imgId_mod' => 15,  //要修改的图片id
            'img_mod_name'   => 'a_2.jpg',
            //type 5
            'img_num' => 2, //新上传2张
            'img_name_1' => 'b_2.jpg',
            'img_name_2' => 'c_2.jpg',
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/blog_modify";
        */
        /*
        //发表文章评论 + 回复
        $data = array(
            'type' => 2, // 1 评论， 2 回复
            'uid'  => 1, //评论或回复的uid
            'touid' => 1, //被回复的uid
            'aid' => 1, //文章id
            'content' => '回复的回复。。。',
            'a_c_id' => 1, // 被回复的文章评论id   type = 1时置0
            'a_c_r_id' => 1, //被回复的回复id type=1时 置0
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
            
        );
        $url = "http://i.youqu.intra.weibo.com/home_postarticlereply";
        */
        
        /*
        $data = array(
            'type' => 1, // 1 文章点赞， 2 回复点赞
            'uid'  => 5, 
            'aid' => 1, //文章id
            'a_c_id' => 1, 
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
        
        );
        $url = "http://i.youqu.intra.weibo.com/home_articlefavor";
        */
        /*
        $data = array(
            'type' => 1, // 1 来自引导推荐， 2 来自选择游戏
            'uid'  => 2,
            'gids' => '3,4', //游戏id  多个用都号隔开
            'atime'     => date('Y-m-d H:i:s'), 
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/game_gameadd";
        */

        /*
        //举报 文章
        $data = array(
            'uid' => 1,
            'aid' => 1,
            'content' => '举报举报举报举报举报举报',
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/home_articlecomplain";
        */

        /*
        //发送手机短信
        $data = array(
            'uid' => 1,
            'tel' => '18955702391',
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/home_sendmsg";
        */

        /*
        //用户申请新游戏
        $data = array(
            'uid' => 1,
            'checkCode' => '694517',
            'gameName' => '愤怒的小鸟',
            'atime'     => date('Y-m-d H:i:s'),
            'ctime'     => time()
        );
        $url = "http://i.youqu.intra.weibo.com/game_userapply";
        */
        /*
        //用户注册
        $data = array();
        $url = 'http://i.youqu.intra.weibo.com/me_register';
        */

        //获取图片上传token
        $data = array(
            'uid' => 1,
            'pic_num' => 5,
            'pic_name_1' => 'default_head_img.jpg',
            'pic_name_2' => 'b.jpg',
            'pic_name_3' => 'c.jpg',
            'pic_name_4' => 'd.jpg',
            'pic_name_5' => 'e.jpg',
        );
        $url = "http://i.youqu.intra.weibo.com/home_getimagetoken";

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