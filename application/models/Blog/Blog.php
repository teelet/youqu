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
            $insert_id = $instance->getLastInsertId()[0]['last_insert_id()'];
            //社区帖子数 +1
            Group_GamegroupModel::gameGroupActionCountAdd($data['g_g_id'], 1);
            return $insert_id;
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
        //////////////
        if($type == 1){ //回帖
            $field = array(
                'uid'      => $data['uid'],  //评论者的uid
                'bid'      => $data['bid'],
                'content'  => $data['content'],
                'pic_num'  => $data['pic_num'],
                'atime'    => $data['atime'],
                'ctime'    => $data['ctime']
            );
            $res = $instance->insert('blog_comment', $field);
            if($res == 1){ //插入成功
                $insert_id = $instance->getLastInsertId()[0]['last_insert_id()'];
                //将用户回帖顺序记录redis
                $config_cache = Comm_Config::getIni('sprintf.blog.comment.timeorder.bid');
                Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $data['bid']), time(), $insert_id);
            
                if($data['uid'] == $data['buid']){ //单独保存楼主的评论  用户（只看楼主）
                    $config_cache = Comm_Config::getIni('sprintf.blog.comment.timeorder.bid.user');
                    Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $data['bid'], $data['buid']), time(), $insert_id);
                }
                //回帖数 +1
                self::blogActionCountAdd($data['bid'], 1);
                return $insert_id;
            }
        }elseif($type == 2){ //回复
            $field = array(
                'uid'        => $data['uid'],  //评论者的uid
                'bid'        => $data['bid'],
                'touid'      => $data['touid'],
                'b_c_id'     => $data['b_c_id'],
                'f_b_c_c_id' => $data['b_c_c_id'], 
                'content'    => $data['content'],
                'atime'      => $data['atime'],
                'ctime'      => $data['ctime']
            );
            $res = $instance->insert('blog_comment_reply', $field);
            if($res == 1){ //插入成功
                $insert_id = $instance->getLastInsertId()[0]['last_insert_id()'];
                //将用户回复顺序记录redis
                $config_cache = Comm_Config::getIni('sprintf.blog.comment.timeorder.b_c_id');
                Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $data['b_c_id']), time(), $insert_id);
                //回复数 +1
                self::blogCommentActionCountAdd($data['b_c_id'], 1);
                
                return $insert_id;
            }
        }
        
        return false;
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
     * urls 图片url数组 形如 array('http://www.abc.com/a.jpg', 'http://www.abc.com/b.jpg');
     */
    public static function insertBlogImage($bid, array $urls){
        if(! is_numeric($bid) || $bid <= 0 || empty($urls)){
            return false;
        }
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        foreach($urls as $url){
            $field = array(
                'bid'   => $bid,
                'url_2' => $url, //原图
                'type'  => 2, //原图
                'atime' => date('Y-m-d H:i:s'),
                'ctime' => time()
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
     * urls 图片url数组 形如 array('http://www.abc.com/a.jpg', 'http://www.abc.com/b.jpg');
     */
    public static function insertBlogReplyImage($bid, $b_c_id, array $urls){
        if(! is_numeric($bid) || $bid <= 0 || ! is_numeric($b_c_id) || $b_c_id <= 0 || empty($urls)){
            return false;
        }
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        foreach($urls as $url){
            $field = array(
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
            $blog = $instance->field('bid, type, show_index')->where('g_g_id = '.$g_g_id)->order(array('type' => 'asc', 'show_index' => 'asc'))->select('blog_top');
            if(! empty($blog)){ //入redis
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
            $sql = 'select a.bid,a.type, a.uid, a.pic_num, a.title, a.content, a.address, a.g_g_id, a.atime, b.name as g_g_name from blog a inner join game_group b on a.g_g_id = b.g_g_id where a.status = 0 and a.bid = '.$bid.' limit 1';
            $blog = $instance->doSql($sql)[0];
            if(! empty($blog)){
                //入redis
                Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($blog));
            }
        }
        return $blog;
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
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $card = $instance->field('uid, bid, title, type')->where('bid = '.$bid)->limit(1)->select('blog')[0];
            if(! empty($card)){
                //入redis
                Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($card));
            }
        }
        return $card;
    }
    
    /*
     * 获取帖子的图片
     */
    public static function getBlogImage($bid){
        if(! is_numeric($bid) || $bid <= 0){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.read');
        $config_cache = Comm_Config::getIni('sprintf.blog.imageinfo');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $imageInfo = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)), true);
        if(! $imageInfo){ //从db中取
                //获取数据库配置文件
                $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
                $instance = Comm_Db_Handler::getInstance(self::$db, $config);
                $imageInfo = $instance->field('*')->where("bid = $bid")->select('blog_images');
                if(! empty($imageInfo)){
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
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.read');
        $config_cache = Comm_Config::getIni('sprintf.blog.action_count');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog_action_count = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)), true);
        if(! $blog_action_count){ //从db中取
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $blog_action_count = $instance->field('*')->where("bid = $bid")->select('blog_action_count')[0];
            if(! empty($blog_action_count)){
                //入redis
                Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($blog_action_count));
            }
        }
        return $blog_action_count;
    }
    
    /*
     * 获取帖子评论的转评赞数
     */
    public static function getBlogCommentActionCount($b_c_id){
        if(! is_numeric($b_c_id) || $b_c_id <= 0){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.read');
        $config_cache = Comm_Config::getIni('sprintf.blog.comment.action_count');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog_comment_action_count = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $b_c_id)), true);
        if(! $blog_comment_action_count){ //从db中取
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $blog_comment_action_count = $instance->field('*')->where("b_c_id = $b_c_id")->select('blog_comment_action_count')[0];
            if(! empty($blog_comment_action_count)){
                //入redis
                Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $b_c_id), $config_cache['expire'], json_encode($blog_comment_action_count));
            }
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
     * 管理更新帖子
     * type 1 更新标题， 2 更新正文
     */
    public static function modify($uid, $bid, $type, $content){
        if(empty($uid) || empty($bid) || empty($type)){
            return false;
        }
        //更新redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        //----------------
        $config_cache = Comm_Config::getIni('sprintf.blog.content');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $a = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)),true);
        if($a){
            if($type == 1){
                $a['title'] = $content;
            }else{
                $a['content'] = $content;
            }
        }
        Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($a));
        //----------------
        if($type == 1){
            $config_cache = Comm_Config::getIni('sprintf.blog.blog_card');
            $card = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $bid)), true);
            if($card){
                $card['title'] = $content;
            }
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $bid), $config_cache['expire'], json_encode($card));
        }
        
        //更新db
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        if($type == 1){
            $instance->where(array('bid'=>$bid))->update('blog',array('title'=>$content));
        }else{
            $instance->where(array('bid'=>$bid))->update('blog',array('content'=>$content));
        }
        return 1;
        
    }       
    /*
     * 管理员操作（置顶，加精，删除）
     * type 1 置顶， 2 加精， 3 删除
     */
    public static function manage($uid, $g_g_id, $bid, $type, $atime, $ctime){
        if(empty($uid) || empty($bid) || empty($type) || empty($g_g_id)){
            return false;
        }
        //检查该管理员的操作次数是否已过
        $count = self::getManagerCharge($g_g_id, $uid, $atime);
        if($count['used'] >= $count['all']){
            return -1;
        }
        switch ($type){
            case 1 :
                $res = self::addTop($g_g_id, $bid, $uid);
                break;
            case 2 :
                $res = self::addGreat($g_g_id, $bid, $uid);
                break;
            case 3 :
                $res = self::delBlog($g_g_id, $bid, $uid);
                break;
        }
        return $res;
    }
    /*
     * 删除blog
     */
    public static function delBlog($g_g_id, $bid, $uid){
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
        
        //更新db
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $instance->where(array('bid'=>$bid))->update('blog',array('status'=>1));
        
        return 1;
    }
    
    /*
     * 加精操作
     * uid 管理员id
     */
    public static function addGreat($g_g_id, $bid, $uid){
        if(empty($uid) || empty($bid) || empty($g_g_id)){
            return false;
        }
        //更新redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.blog_top');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $g_g_id)), true);
        $a = array(
            'bid' => $bid,
            'type' => 1
        );
        $blog[] = $a;
        Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $g_g_id), $config_cache['expire'], json_encode($blog));
        //更新db
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $instance->insert('blog_top', $a);
        return 1;
    }
    
    /*
     * 置顶操作
     * uid 管理员id
     */
    public static function addTop($g_g_id, $bid, $uid){
        if(empty($uid) || empty($bid) || empty($g_g_id)){
            return false;
        }
        //更新redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.blog_top');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $blog = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $g_g_id)), true);
        $a = array(
            'bid' => $bid,
            'type' => 0
        );
        $blog[] = $a;
        Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $g_g_id), $config_cache['expire'], json_encode($blog));
        //更新db
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $instance->insert('blog_top', $a);
        return 1;
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
        $res2 = $instance->field('count(id) as count')->where('g_g_id = '.$g_g_id.' and uid = '.$uid.' and atime > '.$time)->select('game_group_manager_log')[0]['count'];
        
        return array(
            'all'  => $res1,
            'used' => $res2
        );
        
    }
    
}



















