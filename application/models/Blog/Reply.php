<?php

/**
 * blog reply
 * shaohua
 */

class Blog_ReplyModel {
    
    private static $db = 'gameinfo';  //库名
    
    /*
     * 获取帖子 的回帖
     * 如果传了uid 表示只获取这个用户的回帖 （例如 只看楼主）
     */
    public static function getBlogReply($bid, $start = 0, $pagesize = 10, $uid = 0){
        if(empty($bid)){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        if($uid != 0){  //查看指定用户的回帖
            //从redis中获取回帖的顺序
            $config_cache = Comm_Config::getIni('sprintf.blog.comment.timeorder.bid.user');
            $list = Comm_Redis_Redis::zrange($redis, sprintf($config_cache['key'], $bid, $uid), $start, $start + $pagesize);
        }else{ //查看所有回帖
            //从redis中获取回帖的顺序
            $config_cache = Comm_Config::getIni('sprintf.blog.comment.timeorder.bid');
            $list = Comm_Redis_Redis::zrange($redis, sprintf($config_cache['key'], $bid), $start, $start + $pagesize);
        }
        
        if(empty($list)){
            return false;
        }else{ //取回帖的详细信息
            return self::getBlogReplyDetail(array_keys($list));
        }
        
    }
    
    /*
     * 回帖的详细信息
     * b_c_ids 回帖id数组  array(1,2,3)
     */
    public static function getBlogReplyDetail(array $b_c_ids){
        if(empty($b_c_ids)){
            return false;
        }
        //获取基本信息
        $exsist = self::getBlogCommentBaseInfo($b_c_ids);
        $uids = array();
        if(count($exsist) > 0){
            foreach ($exsist as $k => $v){
                $uids[] = $v['uid'];
                //获取回帖图片
                if($v['pic_num'] > 0){
                    $imageinfo = self::getBlogCommentImage($v['b_c_id']);
                    if(! empty($imageinfo)){
                        foreach ($imageinfo as $image){
                            $exsist[$k]['images'][] = $image['url_2'];
                        }
                    }
                }
                
                //获取回帖的转评赞数量
                $count = Blog_BlogModel::getBlogCommentActionCount($v['b_c_id']);
                isset($count['reply_num']) ? $exsist[$k]['reply_num'] = $count['reply_num'] : $exsist[$k]['reply_num'] = 0;
                isset($count['favor_num']) ? $exsist[$k]['favor_num'] = $count['favor_num'] : $exsist[$k]['favor_num'] = 0;
                //获取回帖的回复信息
                
                if($count['reply_num'] > 0){
                    $res = self::getBlogCommentReply($v['b_c_id'], 0, 10);
                    if(! empty($res)){
                        foreach ($res as $value){
                            $uids[] = $value['uid'];
                            $exsist[$k]['reply'][] = array(
                                'b_c_c_id' => $value['b_c_c_id'],
                                'uid'      => $value['uid'],
                                'touid'    => $value['touid'],
                                'content'  => $value['content'],
                                'atime'    => $value['atime'],
                                'ctime'    => $value['ctime']
                            );
                        }
                    }
                }
            }
        }
        
        //获取用信息
        $user_info = array();
        if(count($uids) > 0){
            $uids = array_unique($uids);
            $user_info = User_UserModel::getUserInfos($uids);
        }
        
        //返回结果
        $result = array();
        if(count($exsist) > 0){
            foreach ($b_c_ids as $b_c_id){
                if(isset($exsist[$b_c_id])){                    
                    $result['list'][] = $exsist[$b_c_id];
                }
            }
        }
        $result['userinfo'] = $user_info;
        
        return $result;
    }
    
    /*
     * 获取回复信息
     * b_c_id  回帖的id
     */
    public static function getBlogCommentReply($b_c_id, $start = 0, $pagesize = 10){
        //从redis中获取回帖的顺序
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.blog.comment.timeorder.b_c_id');
        $list = Comm_Redis_Redis::zrange($redis, sprintf($config_cache['key'], $b_c_id), $start, $start + $pagesize);
        if(! empty($list)){ //取回复的内容
            return self::getBlogCommentReplyBaseInfo(array_keys($list));
        }
        return false;
    }
    /*
     * 回复的基本信息
     */
    public static function getBlogCommentReplyBaseInfo(array $b_c_c_ids){
        if(empty($b_c_c_ids)){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.comment.reply.content');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $keys = array();
        $exsist = array();
        $notexsist = array();
        foreach ($b_c_c_ids as $b_c_c_id){
            $keys[sprintf($config_cache['key'], $b_c_c_id)] = $b_c_c_id;
        }
        
        $list = Comm_Redis_Redis::mget($redis, array_keys($keys));
        foreach ($list as $k => $v){
            empty($v) ? $notexsist[] = $keys[$k] : $exsist[$keys[$k]] = json_decode($v, true) ;
        }
        if(count($notexsist) > 0){ //从db中取
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $sql = 'select * from blog_comment_reply where status = 0 and b_c_c_id in ('.implode(',', $notexsist).')';
            $res = $instance->doSql($sql);
            if($res){
                foreach ($res as $k => $v){
                    //将数据如redis
                    $b_c_c_id = $v['b_c_c_id'];
                    Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $b_c_c_id), $config_cache['expire'], json_encode($v));
                    $exsist[$b_c_c_id] = $v;
                }
            }
        }
    
        return $exsist;
    }
    
    
    /*
     * 回帖的基本信息
     */
    public static function getBlogCommentBaseInfo(array $b_c_ids){
        if(empty($b_c_ids)){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.blog.comment.content');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $keys = array();
        $exsist = array();
        $notexsist = array();
        foreach ($b_c_ids as $b_c_id){
            $keys[sprintf($config_cache['key'], $b_c_id)] = $b_c_id;
        }
        $list = Comm_Redis_Redis::mget($redis, array_keys($keys));
        foreach ($list as $k => $v){
            empty($v) ? $notexsist[] = $keys[$k] : $exsist[$keys[$k]] = json_decode($v, true) ;
        }
        if(count($notexsist) > 0){ //从db中取
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $sql = 'select b_c_id, bid, uid, content, pic_num, atime, ctime from blog_comment where status = 0 and b_c_id in ('.implode(',', $notexsist).')';
            $res = $instance->doSql($sql);
            if($res){
                foreach ($res as $k => $v){
                    //将数据如redis
                    $b_c_id = $v['b_c_id'];
                    Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $b_c_id), $config_cache['expire'], json_encode($v));
                    $exsist[$b_c_id] = $v;
                }
            }
        }
        
        return $exsist;
    }
    
    
    /*
     * 获取回帖的图片
     */
    public static function getBlogCommentImage($b_c_id){
        if(! is_numeric($b_c_id) || $b_c_id <= 0){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.read');
        $config_cache = Comm_Config::getIni('sprintf.blog.comment.imageinfo');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $imageInfo = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $b_c_id)), true);
        if(! $imageInfo){ //从db中取
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $imageInfo = $instance->field('*')->where("b_c_id = $b_c_id")->select('blog_comment_images');
            if(! empty($imageInfo)){
                //入redis
                Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $b_c_id), $config_cache['expire'], json_encode($imageInfo));
            }
        }
        return $imageInfo;
    }
    
    /*
     * 点赞
     */
    public static function favor(&$data){
        if(empty($data)){
            return false;
        }
        
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);

        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        
        if($data['type'] == 1){ //给帖子点赞
            //入redis
            $config_cache = Comm_Config::getIni('sprintf.blog.favor');
            Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $data['bid']), time(), $data['uid']);
            $a = Comm_Redis_Redis::zrank($redis, sprintf($config_cache['key'], $data['bid']), $data['uid']);
            if($a !== false){ //用户已赞
                return -1;
            
            }
            //点赞数 +1
            Blog_BlogModel::blogActionCountAdd($data['bid'], 0);
            
            //入db
            $field = array(
                'bid'   => $data['bid'],
                'uid'   => $data['uid'],
                'atime' => $data['atime'],
                'ctime' => $data['ctime']
            );
            $res = $instance->insert('blog_favor_log', $field);
            
        }elseif($data['type'] == 2){ //给回帖点赞
            //入redis
            $config_cache = Comm_Config::getIni('sprintf.blog.favor.comment');
            $a = Comm_Redis_Redis::zrank($redis, sprintf($config_cache['key'], $data['b_c_id']), $data['uid']);
            if($a !== false){ //用户已赞
                return -1;
                
            }
            Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $data['b_c_id']), time(), $data['uid']);
            //点赞数 +1
            Blog_BlogModel::blogCommentActionCountAdd($data['b_c_id'], 0);
            
            //入db
            $field = array(
                'b_c_id'   => $data['b_c_id'],
                'uid'   => $data['uid'],
                'atime' => $data['atime'],
                'ctime' => $data['ctime']
            );
            $res = $instance->insert('blog_comment_favor_log', $field);
            
        }
        
        return $res;
    }
    
    /*
     * 获取点赞信息
     * type 1 帖子的点赞信息 ， 2 回帖的点赞信息
     */
    public static function getFavor($id, $type = 1, $start = 0, $pagesize = 10){
        if(empty($id) || empty($type)){
            return false;
        }
    
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.read');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
    
        if($type == 1){ //帖子
            $config_cache = Comm_Config::getIni('sprintf.blog.favor');
        }elseif($type == 2){ //回帖
            $config_cache = Comm_Config::getIni('sprintf.blog.favor.comment');
        }
        $favor = Comm_Redis_Redis::zrange($redis, sprintf($config_cache['key'], $id), $start, $pagesize);
        return array_keys($favor);
    }
    
    
    
}














