<?php

/**
 * blog
 * shaohua
 */

class Blog_BlogModel {
    
    private static $db = 'gameinfo';  //库名
    
    /*
     * 插入帖子
     */
    public static function insertBlog(&$data){
        if(empty($data)){
            return false;
        }
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $field = array(
            'bid'     => $data['bid'],
            'uid'     => $data['uid'],
            'g_g_id'  => $data['g_g_id'],
            'title'   => $data['title'],
            'content' => $data['content'],
            'address' => $data['address'],
            'pic_num' => $data['pic_num'],
            'atime'   => $data['atime'],
            'ctime'   => $data['ctime']
        );
        $res = $instance->insert('blog', $field);
        if($res == 1){ //插入成功
            //社区帖子数 +1
            Group_GamegroupModel::gameGroupActionCountAdd($data['g_g_id'], 1);
            return true;
        }else{
            return false;
        }
    }
    
    /*
     * 插入回帖+回复
     * type 1 回帖 ，2 回复
     */
    public static function insertBlogReply(&$data, $type = 1){
        if(empty($data)){
            return false;
        }
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        
        if($type == 1){ //回帖
            $field = array(
                'b_c_id'   => $data['b_c_id'],
                'uid'      => $data['uid'],  //评论者的uid
                'bid'      => $data['bid'],
                'content'  => $data['content'],
                'pic_num'  => $data['pic_num'],
                'atime'    => $data['atime'],
                'ctime'    => $data['ctime'],
                'status'   => $data['status']
            );
            $res = $instance->insert('blog_comment', $field);
            if($res == 1){ //插入成功
                //将用户回帖顺序记录redis
                $config_cache = Comm_Config::getIni('sprintf.blog.comment.timeorder.bid');
                Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $data['bid']), time(), $data['b_c_id']);
            
                if($data['uid'] == $data['buid']){ //单独保存楼主的评论  用户（只看楼主）
                    $config_cache = Comm_Config::getIni('sprintf.blog.comment.timeorder.bid.user');
                    Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $data['bid'], $data['buid']), time(), $data['b_c_id']);
                }
                //回帖数 +1
                self::blogActionCountAdd($data['bid'], 1);
                return 1;
            }
        }elseif($type == 2){ //回复
            $field = array(
                'b_c_c_id'   => $data['b_c_c_id'],
                'uid'        => $data['uid'],  //评论者的uid
                'bid'        => $data['bid'],
                'touid'      => $data['touid'],
                'b_c_id'     => $data['b_c_id'],
                'f_b_c_c_id' => $data['f_b_c_c_id'], 
                'content'    => $data['content'],
                'atime'      => $data['atime'],
                'ctime'      => $data['ctime']
            );
            $res = $instance->insert('blog_comment_reply', $field);
            if($res == 1){ //插入成功
                //将用户回复顺序记录redis
                $config_cache = Comm_Config::getIni('sprintf.blog.comment.timeorder.b_c_id');
                Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $data['b_c_id']), time(), $data['b_c_c_id']);
                //回复数 +1
                self::blogCommentActionCountAdd($data['b_c_id'], 1);
                
                return 1;
            }
        }
        
        return false;
    }
    
    /*
     * 初始化blog的点赞，评论
     */
    public static function initBlogActionCount($bid){
        if(! is_numeric($bid) || $bid <= 0){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.action_count');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog_action_count = array(
            'read_num'    => 0,
            'reply_num'   => 0,
            'collect_num' => 0,
            'favor_num'   => 0
        );
        Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($blog_action_count));
    }
    
    /*
     * 帖子的转评赞数 +1
     * bid
     * type 0 点赞， 1 评论
     */
    public static function blogActionCountAdd($bid, $type = 0){
        if(! is_numeric($bid) || $bid <= 0){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.action_count');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog_action_count = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)), true);
        if($blog_action_count){  //更新缓存
            switch ($type){
                case 0 :
                    $blog_action_count['favor_num']++;
                    break;
                case 1 :
                    $blog_action_count['reply_num']++;
                    break;
            }
            //入redis
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($blog_action_count));
        }
        
        //更新数据库  （以后可以优化 异步）
        //获取数据库配置文件
         $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
         $instance = Comm_Db_Handler::getInstance(self::$db, $config);
         if($type == 0){
            $sql = 'insert into blog_action_count (bid, favor_num) values ('.$bid.', 1) on duplicate key update favor_num = favor_num + 1';
         }elseif($type == 1){
            $sql = 'insert into blog_action_count (bid, reply_num) values ('.$bid.', 1) on duplicate key update reply_num = reply_num + 1';
         }
         $instance->doSql($sql);
    }
    
    /*
     * 回复的转评赞数 +1
     * bid
     * type 0 点赞， 1 评论
     */
    public static function blogCommentActionCountAdd($b_c_id, $type = 0){
        if(! is_numeric($b_c_id) || $b_c_id <= 0){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.comment.action_count');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog_comment_action_count = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $b_c_id)), true);
        if($blog_comment_action_count){  //更新缓存
            switch ($type){
                case 0 :
                    $blog_comment_action_count['favor_num']++;
                    break;
                case 1 :
                    $blog_comment_action_count['reply_num']++;
                    break;
            }
            //入redis
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $b_c_id), $config_cache['expire'], json_encode($blog_comment_action_count));
        }
    
        //更新数据库  （以后可以优化 异步）
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        if($type == 0){
            $sql = 'insert into blog_comment_action_count (b_c_id, favor_num) values ('.$b_c_id.', 1) on duplicate key update favor_num = favor_num + 1';
        }elseif($type == 1){
            $sql = 'insert into blog_comment_action_count (b_c_id, reply_num) values ('.$b_c_id.', 1) on duplicate key update reply_num = reply_num + 1';
        }
        $instance->doSql($sql);
    }
    
    /*
     * 插入图片
     * big 图片所属帖子id
     * urls 图片url数组 形如 array( 1 => 'http://www.abc.com/a.jpg', 2 => 'http://www.abc.com/b.jpg');
     */
    public static function insertBlogImage($bid, array $urls){
        if(! is_numeric($bid) || $bid <= 0 || empty($urls)){
            return false;
        }
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        foreach($urls as $key => $url){
            $field = array(
                'b_i_id' => $key,
                'bid'    => $bid,
                'url_2'  => $url, //原图
                'atime'  => date('Y-m-d H:i:s'),
                'ctime'  => time()
            );
            $res = $instance->insert('blog_images', $field);
            if($res != 1){
                return false;
            }
        }
        return true;
    }
    
    /*
     * 插入回帖图片
     * big 图片所属帖子id
     * b_c_id 回帖id
     * urls 图片url数组 形如 array( 1 => 'http://www.abc.com/a.jpg', 2 => 'http://www.abc.com/b.jpg');
     */
    public static function insertBlogReplyImage($bid, $b_c_id, array $urls){
        if(! is_numeric($bid) || $bid <= 0 || ! is_numeric($b_c_id) || $b_c_id <= 0 || empty($urls)){
            return false;
        }
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        foreach($urls as $key => $url){
            $field = array(
                'b_c_id_id' => $key,
                'bid'    => $bid,
                'b_c_id' => $b_c_id,
                'url_2'  => $url, //原图
                'atime'  => date('Y-m-d H:i:s'),
                'ctime'  => time()
            );
            $res = $instance->insert('blog_comment_images', $field);
            if($res != 1){
                return false;
            }
        }
        return true;
    }
    
    /*
     * 获取社区的置顶帖
     * g_g_id 社区id
     */
    public static function getBlogTop($g_g_id){
        if(! is_numeric($g_g_id) || $g_g_id <= 0){
            return false;
        }
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.read');
        $config_cache = Comm_Config::getIni('sprintf.blog.blog_top');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $g_g_id)), true);
        if(empty($blog)){//db
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $res = $instance->field('bid, type, g_g_id, show_index')->where('g_g_id = '.$g_g_id)->order(array('type' => 'asc', 'show_index' => 'asc'))->select('blog_top');
            if(! empty($res)){ //入redis
                foreach ($res as $v){
                    $blog[$v['bid']] = $v;
                }
                Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $g_g_id), $config_cache['expire'], json_encode($blog));
            }
        }
        return $blog;
    }
    
    /*
     * 获取帖子详细
     */
    public static function getBlogDetail($bid){
        if(! is_numeric($bid) || $bid <= 0){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.content');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)), true);
        
        if(! $blog){
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $sql = 'select a.bid,a.type, a.uid, a.pic_num, a.title, a.content, a.address, a.status, a.g_g_id, a.atime, a.ctime, b.name as g_g_name from blog a inner join game_group b on a.g_g_id = b.g_g_id where a.bid = '.$bid.' limit 1';
            $blog = $instance->doSql($sql)[0];
            if(! empty($blog)){
                //入redis
                Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($blog));
            }
        }
        return $blog;
    }
    
    /*
     * 预埋blog的缓存
     */
    public static function setBlogDetail(&$data){
        if(empty($data)){
            return false;
        }
        $blog = array(
                'bid'       => $data['bid'],
                'type'      => 0,  //帖子类型 0 普通，1 精华  默认 0
                'uid'       => $data['uid'],
                'pic_num'   => $data['pic_num'],
                'title'     => $data['title'],
                'content'   => $data['content'],
                'address'   => $data['address'],
                'atime'     => $data['atime'],
                'ctime'     => $data['ctime'],
                'g_g_id'    => $data['g_g_id'],
                'g_g_name'  => Group_GamegroupModel::getGroupInfo($data['g_g_id'])['name'],
                'status'    => 0 //帖子转态 0 正常， 1 下架  默认 0
            );
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.content');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $data['bid']), $config_cache['expire'], json_encode($blog));
    }
    
    /*
     * 获取帖子详细
     */
    public static function getBlogCard($bid){
        if(! is_numeric($bid) || $bid <= 0){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.blog_card');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $card = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)), true);
        if(! $card){
            $blog = self::getBlogDetail($bid);
            if($blog){
                $card = array(
                    'uid'   => $blog['uid'],
                    'bid'   => $blog['bid'],
                    'type'  => $blog['type'],
                    'title' => $blog['title'],
                );
                //入redis
                Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($card));
            }
        }
        return $card;
    }
    
    /*
     * 拼接帖子图片信息如redis
     */
    public static function setBlogImage(&$data){
        if(empty($data)){
            return false;
        }
        $imageInfo = array();
        $bid = null;
        foreach ($data as $b_i_id => $v){
            $imageInfo[$b_i_id] = array(
                'b_i_id'  => $v['b_i_id'],
                'bid'     => $v['bid'],
                'url_0'   => $v['url_0'],
                'url_1'   => $v['url_1'],
                'url_2'   => $v['url_2'],
                'atime'   => $v['atime'],
                'ctime'   => $v['ctime'],
                'summary' => $v['summary'],
                'status'  => 0
            );
            $bid = $v['bid'];
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.imageinfo');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($imageInfo));
    }
    
    /*
     * 获取帖子的图片
     */
    public static function getBlogImage($bid){
        if(! is_numeric($bid) || $bid <= 0){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.imageinfo');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $imageInfo = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)), true);
        if(! $imageInfo){ //从db中取
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $info = $instance->field('*')->where("bid = $bid")->select('blog_images');
            $imageInfo = array();
            if(! empty($info)){
                foreach ($info as $v){
                    $imageInfo[$v['b_i_id']] = $v;
                }
                //入redis
                Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($imageInfo));
            }
        }
        return $imageInfo;
    }
    
    /*
     * 获取帖子的转评赞数
     */
    public static function getBlogActionCount($bid){
        if(! is_numeric($bid) || $bid <= 0){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.action_count');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog_action_count = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)), true);
        if(! $blog_action_count){ //从db中取
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $blog_action_count = $instance->field('read_num, reply_num, collect_num, favor_num')->where("bid = $bid")->select('blog_action_count')[0];
            if(empty($blog_action_count)){
                $blog_action_count = array(
                    'read_num'    => 0,
                    'reply_num'   => 0,
                    'collect_num' => 0,
                    'favor_num'   => 0
                );
            }
            //入redis
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($blog_action_count));
            
        }
        return $blog_action_count;
    }
    
    /*
     * 初始化回帖的转评赞数
     */
    public static function initBlogCommentActionCount($b_c_id){
        if(! is_numeric($b_c_id) || $b_c_id <= 0){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.comment.action_count');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $count = array(
            'b_c_id'    => $b_c_id,
            'reply_num' => 0,
            'favor_num' => 0
        );
        Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $b_c_id), $config_cache['expire'], json_encode($count));
    }
    
    /*
     * 获取回帖的转评赞数
     */
    public static function getBlogCommentActionCount($b_c_id){
        if(! is_numeric($b_c_id) || $b_c_id <= 0){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.comment.action_count');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog_comment_action_count = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $b_c_id)), true);
        if(! $blog_comment_action_count){ //从db中取
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $blog_comment_action_count = $instance->field('b_c_id, reply_num, favor_num')->where("b_c_id = $b_c_id")->select('blog_comment_action_count')[0];
            if(empty($blog_comment_action_count)){
                $blog_comment_action_count = array(
                    'b_c_id'    => $b_c_id,
                    'reply_num' => 0,
                    'favor_num' => 0
                );
            }
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $b_c_id), $config_cache['expire'], json_encode($blog_comment_action_count));
        }
        return $blog_comment_action_count;
    }
    
    /*
     * 向普通帖集合中添加元素
     */
    public static function addToGroupBlog($g_g_id, $bid){
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.group.group.blog_list.all');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $g_g_id), time(), $bid);
        //降级
        $ttl = Comm_Redis_Redis::ttl($redis, sprintf($config_cache['key'], $g_g_id));
        if($ttl <= 0){
            Comm_Redis_Redis::del($redis, sprintf($config_cache['key'], $g_g_id));
        }
    }
    
    /*
     * 向社区用户帖子集合中添加元素
     */
    public static function addToUserBlog($g_g_id, $uid, $bid){
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.group.group_user_sign');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $g_g_id, $uid), time(), $bid);
    }
    
    /*
     * 获取用户社区帖子列表
     */
    public static function getUserBlog($g_g_id, $uid, $start, $pagesize){
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.group.group_user_sign');
        $blog = array_keys(Comm_Redis_Redis::zrevrange($redis, sprintf($config_cache['key'], $g_g_id, $uid), $start, $start + $pagesize - 1));
        if(empty($blog)){
            //db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config); 
            $blog = $instance->field('bid')->where('g_g_id = '.$g_g_id.' and uid = '.$uid)->order(array('bid' => 'desc'))->select('blog');
            if($blog){ //入redis
                $blog_rev = array_reverse($blog);
                foreach ($blog_rev as $value){
                    Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $g_g_id, $uid), time(), $value['bid']);
                }
                $res = array_slice($blog, $start, $pagesize);
                $blog = array();
                foreach ($res as $value){
                    $blog[] = (int)$value['bid'];
                }
            }
        }
            return $blog;
    }
    
    /*
     * 获取单个社区首页的帖子
     * g_g_id  社区id
     * type  帖子类型  0 全部，1精华帖
     */
    public static function getGroupBlog($g_g_id, $start = 0, $pagesize = 10, $type = 0){
        if(empty($g_g_id)){
            return $g_g_id;
        }
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        if($type == 0){ //全部
            $config_cache = Comm_Config::getIni('sprintf.group.group.blog_list.all');
        }else{
            $config_cache = Comm_Config::getIni('sprintf.group.group.blog_list.great');
        }
        $group_list = array_keys(Comm_Redis_Redis::zrevrange($redis, sprintf($config_cache['key'], $g_g_id), $start, $pagesize));
        if(empty($group_list)){ //取db
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            if($type == 0){
                $list = $instance->field('bid')->where('g_g_id = '.$g_g_id)->order(array('bid' => 'desc'))->limit(500)->select('blog');
            }else{
                $list = $instance->field('bid')->where('g_g_id = '.$g_g_id.' and type = 1')->order(array('bid' => 'desc'))->limit(500)->select('blog');
            }
            if($list){
                $list_rev = array_reverse($list);
                foreach ($list_rev as $value){//入redis
                    Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $g_g_id), time(), $value['bid']);
                }
                Comm_Redis_Redis::expire($redis, sprintf($config_cache['key'], $g_g_id), $config_cache['expire']);
                $list = array_slice($list, $start, $pagesize);
                foreach ($list as $value){
                    $group_list[] = (int)$value['bid'];
                }
            }
        }
        
        return $group_list;
        
    }
    
    /*
     * 获取社区首页的帖子
     * g_g_id  社区id
     * type  帖子类型  0 全部，1精华帖
     */
    public static function getHomeBlog($start = 0, $pagesize = 10){
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.group.group.home.blog_list');
        $group_list = array_keys(Comm_Redis_Redis::zrevrange($redis, $config_cache['key'], $start, $pagesize));
        if(empty($group_list)){ //取db
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $list = $instance->field('bid')->where('type = 1')->order(array('bid' => 'desc'))->limit(500)->select('blog');
            if($list){
                $list_rev = array_reverse($list);
                foreach ($list_rev as $value){//入redis
                    Comm_Redis_Redis::zadd($redis, $config_cache['key'], time(), $value['bid']);
                }
                Comm_Redis_Redis::expire($redis, $config_cache['key'], $config_cache['expire']);
                $list = array_slice($list, 0, 2);
                foreach ($list as $value){
                    $group_list[] = (int)$value['bid'];
                }
            }
        }
        return $group_list;
    }
    
    /*
     * 举报
     */
    public static function complain(&$data){
        if(empty($data['bid']) || empty($data['uid'])){
            return false;
        }
        $field = array(
            'uid' => $data['uid'],
            'bid' => $data['bid'],
            'content' => $data['content'],
            'atime' => $data['atime'],
            'ctime' => $data['ctime']
        );
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        return $instance->insert('blog_complain', $field);
    }
    
    /*
     * 删除帖子图片
     * imgIds 形如 array(1, 2, 3)
     * is_own 1 用户自己，0否
     */
    public static function delBlogImg($g_g_id, $uid, $bid, array $imgIds, $atime, $is_own = 0){
        if(empty($bid) || empty($imgIds)){
            return false;
        }
        if($is_own === 0){
            //检查该管理员的操作次数是否已过
            $count = self::getManagerCharge($g_g_id, $uid, $atime);
            if($count['used'] >= $count['all']){
                return -1;
            }
        }
        
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        //更新redis图片缓存信息
        $config_cache = Comm_Config::getIni('sprintf.blog.imageinfo');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $imageInfo = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)), true);
        if(! empty($imageInfo)){
            foreach ($imgIds as $v){
                if(isset($imageInfo[$v])){
                    unset($imageInfo[$v]);
                }else{
                    unset($imgIds[$v]);
                }
            }
            //入redis
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($imageInfo));
        }
        
        //更新redis的blog正文
        $count = count($imgIds);
        $config_cache = Comm_Config::getIni('sprintf.blog.content');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)),true);
        if(! empty($blog)){
            $blog['pic_num'] = $blog['pic_num'] - $count;
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($blog));
        }
        
        //更新blog的db
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $sql = 'update blog set pic_num = pic_num - '.$count.' where bid = '.$bid;
        $instance->doSql($sql);
        //更新blog_images 的db
        $sql = 'delete from blog_images where bid = '.$bid.' and b_i_id in ('.implode(',', $imgIds).')';
        $instance->doSql($sql);
        
        if($is_own === 0){
            self::managerLogAdd($g_g_id, $bid, $uid, 4);
            self::setBlogManagerLog($bid, $uid, 4, $atime);
        }
        
        return 1;
    }
    
    /*
     * 修改帖子字段
     * 修改field字段的内容为content
     */
    public static function updateBlog($bid, $field, $content){
        if(empty($bid) || empty($field)){
            return false;
        }
        //更新redis的blog正文
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.content');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)),true);
        if(! empty($blog)){
            $blog[$field] = $content;
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($blog));
        }
        //更新redis的blog的card(bid,uid,type,title)
        $config_cache = Comm_Config::getIni('sprintf.blog.blog_card');
        $card = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)),true);
        if(isset($card[$field])){
            $card[$field] = $content;
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($card));
        }
        
        //更新db
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $instance->where(array('bid' => $bid))->update('blog',array($field => $content));
        return 1;
    }
    
    /*
     * 更新帖子图片
     */
    public static function updateBlogImage($g_g_id, $uid, $bid, $img_id, $img_url, $atime, $is_own = 0){
        if(empty($g_g_id) || empty($uid) || empty($bid)){
            return false;
        }
        if($is_own === 0){
            //检查该管理员的操作次数是否已过
            $count = self::getManagerCharge($g_g_id, $uid, $atime);
            if($count['used'] >= $count['all']){
                return -1;
            }
        }
        //更新redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.imageinfo');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $imageInfo = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)), true);
        if($imageInfo){
            $imageInfo[$img_id]['url_2'] = $img_url;
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($imageInfo));
        }
        
        //更新db
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $instance->where(array('b_i_id' => $img_id))->update('blog_images', array('url_2' => $img_url));
        
        if($is_own === 0){
            self::managerLogAdd($g_g_id, $bid, $uid, 4);
            self::setBlogManagerLog($bid, $uid, 4, $atime);
        }
        return 1;
    }
    
    /*
     * 更新帖子
     * type 1 更新标题， 2 更新正文
     * is_own 1 用户自己，0否
     */
    public static function modify($g_g_id, $uid, $bid, $type, $content, $atime, $is_own = 0){
        if(empty($g_g_id) || empty($uid) || empty($bid) || empty($type)){
            return false;
        }
        
        if($is_own === 0){
            //检查该管理员的操作次数是否已过
            $count = self::getManagerCharge($g_g_id, $uid, $atime);
            if($count['used'] >= $count['all']){
                return -1;
            }
        }
        
        if($type == 1){
            $field = 'title';
        }elseif($type == 2){
            $field = 'content';
        }
        self::updateBlog($bid, $field, $content);
        if($is_own === 0){
            self::managerLogAdd($g_g_id, $bid, $uid, 4);
            self::setBlogManagerLog($bid, $uid, 4, $atime);
        }
        
        return 1;
    }       
    /*
     * 管理员操作（置顶，加精，删除）
     * type 1 置顶， 2 加精， 3 删除
     */
    public static function manage($uid, $g_g_id, $bid, $type, $atime, $ctime){
        if(empty($g_g_id) || empty($uid) || empty($bid) || empty($type) || empty($g_g_id)){
            return false;
        }
        //检查该管理员的操作次数是否已过
        $count = self::getManagerCharge($g_g_id, $uid, $atime);
        if($count['used'] >= $count['all']){
            return -1;
        }
        
        switch ($type){
            case 1 :
                $res = self::addTop($g_g_id, $bid, $uid, $atime);
                break;
            case 2 :
                $res = self::addGreat($g_g_id, $bid, $uid, $atime);
                break;
            case 3 :
                $res = self::delBlog($g_g_id, $bid, $uid, $atime);
                break;
        }
        self::managerLogAdd($g_g_id, $bid, $uid, $type);
        return $res;
    }
    /*
     * 管理员操作log
     * type 1 置顶， 2 加精， 3 删除，4 修改
     */
    public static function managerLogAdd($g_g_id, $bid, $uid, $type){
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $field = array(
            'g_g_id' => $g_g_id,
            'bid'    => $bid,
            'uid'    => $uid,
            'type'   => $type
        );
        $instance->insert('game_group_manager_log', $field);
    }
    
    /*
     * 删除blog
     * is_own  是否为用户自己  1是 ， 0 否
     */
    public static function delBlog($g_g_id, $bid, $uid, $atime, $is_own = 0){
        if(empty($uid) || empty($bid) || empty($g_g_id)){
            return false;
        }
        //更新redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.group.group.blog_list.all');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        
        Comm_Redis_Redis::zrem($redis, sprintf($config_cache['key'], $g_g_id), $bid);
        $config_cache = Comm_Config::getIni('sprintf.group.group.blog_list.great');
        Comm_Redis_Redis::zrem($redis, sprintf($config_cache['key'], $g_g_id), $bid);
        
        self::updateBlog($bid, 'status', 1);
        if($is_own === 0){
            self::setBlogManagerLog($bid, $uid, 3, $atime);
        }
        
        return 1;
    }
    
    /*
     * 加精操作
     * uid 管理员id
     */
    public static function addGreat($g_g_id, $bid, $uid, $atime){
        if(empty($uid) || empty($bid) || empty($g_g_id)){
            return false;
        }
        
        self::updateBlog($bid, 'type', 1);
        self::setBlogManagerLog($bid, $uid, 2, $atime);
        //若在置顶库中，则更新置顶数据
        $top = self::getBlogTop($g_g_id);
        if(isset($top[$bid])){
            $top[$bid]['type'] = 1;
            //更新redis
            $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
            $config_cache = Comm_Config::getIni('sprintf.blog.blog_top');
            $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $g_g_id), $config_cache['expire'], json_encode($top));
        
            //更新db
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $instance->where(array('g_g_id' => $g_g_id, 'bid' =>$bid))->update('blog_top', array('type' => 1));
        }
        
        return 1;
    }
    
    /*
     * 置顶操作
     * uid 管理员id
     */
    public static function addTop($g_g_id, $bid, $uid, $atime){
        if(empty($uid) || empty($bid) || empty($g_g_id)){
            return false;
        }
        //获取帖子类型 0 普通帖 1 精华帖
        $type = self::getBlogCard($bid)['type'];
        //更新redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.blog_top');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $g_g_id)), true);
        $a = array(
            'bid'        => $bid,
            'type'       => $type,
            'g_g_id'     => $g_g_id,
            'show_index' => 0
        );
        $blog[$bid] = $a;
        Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $g_g_id), $config_cache['expire'], json_encode($blog));
        
        self::setBlogManagerLog($bid, $uid, 1, $atime);
        
        //更新db
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $instance->insert('blog_top', $a);
        return 1;
    }
    
    /*
     * 获取帖子别修改的日志
     */
    public static function getBlogManagerLog($bid){
        if(empty($bid)){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.read');
        $config_cache = Comm_Config::getIni('sprintf.blog.manager_log');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $log = Comm_Redis_Redis::lrange($redis, sprintf($config_cache['key'], $bid), 0, -1);
        return $log;
    }
    
    
    /*
     * 更新帖子别修改的日志
     * type 1 置顶， 2 加精， 3 删除，4 修改
     */
    public static function setBlogManagerLog($bid, $uid, $type, $time){
        if(empty($bid) || empty($uid) || empty($type)){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.manager_log');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $string = sprintf(Comm_Config::getIni('sprintf.blog.manage_string.name'), $bid, $uid, $type, $time);
        Comm_Redis_Redis::lpush($redis, sprintf($config_cache['key'], $bid), $string);
    }
    
    /*
     * 获取管理员是否超出操作次数
     */
    public static function getManagerCharge($g_g_id, $uid, $time){
        if(empty($uid) || empty($g_g_id)){
            return false;
        }
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        //管理员的权限次数
        $res1 = $instance->field('operate_time')->where('g_g_id = '.$g_g_id.' and uid = '.$uid)->limit(1)->select('game_group_manager')[0]['operate_time'];
        //管理员已经操作的次数
        $time = date('Y-m-d', strtotime($time));
        $res2 = $instance->field('count(id) as count')->where('g_g_id = '.$g_g_id.' and uid = '.$uid.' and atime > "'.$time.'"')->select('game_group_manager_log')[0]['count'];
        
        return array(
            'all'  => $res1,
            'used' => $res2
        );
        
    }
    
}
