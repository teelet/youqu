<?php
/**
 * blog
 * shaohua
 */

class Article_ArticleModel {

    private static $db = 'gameinfo';  //库名

    /*
     * 获取文章card
     * aids 文章id数组 array(1,2,3)
     */
    public static function getCardList($aids){
        if(empty($aids)){
            return false;
        }
        //获取card基本信息
        $cards = self::getCardBaseInfo($aids);
        if(! $cards){
            return false;
        }
        //获取点赞信息
        $action_count = self::getArticleActionCounts($aids);
        //获取文章格式
        $article_forms = self::getArticleForms();
        foreach ($cards as $key => $card){
            if(!empty($cards[$key])){
                $cards[$key]['comment_num'] = $action_count[$card['aid']]['comment_num']; //评论数
                $cards[$key]['form_name'] = $article_forms[$card['a_f_id']];
                //获取card图片
                if($card['pic_num'] > 0){
                    $article_images = self::getArticleImg($card['aid'], 0 , 3);
                    if($article_images){
                        foreach ($article_images as $image){
                            $cards[$key]['images'][] = $image['url_2'];
                        }
                    }
                }
            }
        }

        return $cards;
    }

    /*
     * 获取card基本信息
     *  aids 文章id数组 array(1,2,3)
     */
    public static function getCardBaseInfo($aids){
        if(empty($aids)){
            return false;
        }
        $exist = $notexist_key = array();
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.article.card');
        $card_keys = array();
        foreach ($aids as $aid){
            $card_keys[sprintf($config_cache['key'], $aid)] = $aid;
        }
        $cards = Comm_Redis_Redis::mget($redis, array_keys($card_keys));

        foreach ($cards as $key => $card){
            if($card === false){
                $notexist_key[$key] = $card_keys[$key];
            }else{
                $exist[$card_keys[$key]] = json_decode($card, true);
            }
        }

        if(count($notexist_key) > 0){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $sql = 'select aid, title, pic_num, video_url, source, a_f_id, atime, ctime from article where aid in ('.implode(', ', array_values($notexist_key)).') ';
            $res = $instance->doSql($sql);
            $arr = array();
            if($res){
                foreach ($res as $card){
                    $arr[$card['aid']] = $card;
                }
            }
            //入redis
            foreach ($notexist_key as $key => $aid){
                if(isset($arr[$aid])){
                    Comm_Redis_Redis::setex($redis, $key, $config_cache['expire'], json_encode($arr[$aid]));
                }else{
                    $arr[$aid] = array();
                    Comm_Redis_Redis::setex($redis, $key, $config_cache['expire'], json_encode(array()));
                }
            }
            $exist = $exist + $arr;
        }
        $cards = array();
        foreach ($aids as $aid){
            $cards[] = $exist[$aid];
        }
        return $cards;
    }

    
    /*
     * 获取文章详细信息
     * aid 文章id
     */
    public static function getArticleDetail($aid){
        if(!is_numeric($aid) || $aid <= 0){
            return false;
        }
        $article = self::getArticleBaseInfo($aid);
        if(!$article){
            return false;
        }
        //获取文章转评赞数
        $action_count = self::getArticleActionCount($aid);
        if($action_count){
            $article['comment_num'] = $action_count['comment_num'];//评论数
        }
        //获取文章标签
        $article_tags = self::getArticleTags($aid);
        $tag_infos = self::getTagInfos(array_keys($article_tags));
        if($tag_infos){
            foreach ($tag_infos as $tag){
                if(! empty($tag)){
                    $article[tags][] = $tag;
                }
            }
        }
        
        return $article;
    }
    /*
     * 批量获取标签信息
     * tids  array(1,2,3)  标签id数组
     */
    public static function getTagInfos(array $tids){
        if(empty($tids)){
            return false;
        }
        $exist = $notexist_key = array();
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.tag.tag');
        $tag_keys = array();
        foreach ($tids as $tid){
            $tag_keys[sprintf($config_cache['key'], $tid)] = $tid;
        }
        $tags = Comm_Redis_Redis::mget($redis, array_keys($tag_keys));
        foreach ($tags as $key => $tag){
            if($tag === false){
                $notexist_key[$key] = $tag_keys[$key];
            }else{
                $exist[$tag_keys[$key]] = json_decode($tag, true); 
            }
        }
        if(count($notexist_key) > 0){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $sql = 'select tid, name from tag where tid in ('.implode(', ', array_values($notexist_key)).') ';
            $res = $instance->doSql($sql);
            $arr = array();
            if($res){
                foreach ($res as $tag){
                    $arr[$tag['tid']] = $tag;
                }
            }
            //入redis
            foreach ($notexist_key as $key => $tid){
                if(isset($arr[$tid])){
                    Comm_Redis_Redis::setex($redis, $key, $config_cache['expire'], json_encode($arr[$tid]));
                }else{
                    $arr[$tid] = array();
                    Comm_Redis_Redis::setex($redis, $key, $config_cache['expire'], json_encode(array()));
                }
            }
            $exist = $exist + $arr;
        }
        $tags = array();
        foreach ($tids as $tid){
            $tags[] = $exist[$tid];
        }
        return $tags;
    }
    
    /*
     * 获取文章标签
     */
    public static function getArticleTags($aid){
        if(!is_numeric($aid) || $aid <= 0){
            return false;
        }
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.tag.tag.article');
        $article_tags = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $aid)), true);
        if($article_tags === null){
            $article_tags = array();
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $tags = $instance->field('tid, weight')->where(array('aid' => $aid))->order(array('weight' => 'desc'))->select('article_tags');
            if(!empty($tags)){
                foreach ($tags as $tag){
                    $article_tags[$tag['tid']] = $tag;
                }
            }
            //入redis
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $aid), $config_cache['expire'], json_encode($article_tags));
        }
        return $article_tags;
    }
    
    /*
     * 获取评论的转评赞数
     */
    public static function getCommentActionCount($a_c_id){
        if(!is_numeric($a_c_id) || $a_c_id <= 0){
            return false;
        }
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.article.comment.action_count');
        $action_count = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $a_c_id)), true);
        if(empty($action_count)){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $action_count = $instance->field('reply_num, favor_num')->where(array('a_c_id' => $a_c_id))->limit(1)->select('article_comment_action_count')[0];
            if(empty($action_count)){
                $action_count = array(
                    'reply_num' => 0,
                    'favor_num' => 0
                );
            }
            //入redis
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $a_c_id), $config_cache['expire'], json_encode($action_count));
        }
        return $action_count;
    }

    /*
     * 批量获取文章的转评赞
     */
    public static function getArticleActionCounts($aids){
        if(empty($aids)){
            return false;
        }
        $exist = $notexist_key = array();
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.article.action_count');
        $count_keys = array();
        foreach ($aids as $aid){
            $count_keys[sprintf($config_cache['key'], $aid)] = $aid;
        }
        $counts = Comm_Redis_Redis::mget($redis, array_keys($count_keys));

        foreach ($counts as $key => $count){
            if($count === false){
                $notexist_key[$key] = $count_keys[$key];
            }else{
                $exist[$count_keys[$key]] = json_decode($count, true);
            }
        }

        if(count($notexist_key) > 0){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $sql = 'select aid, read_num, comment_num, collect_num, favor_num from article_action_count where aid in ('.implode(', ', array_values($notexist_key)).') ';
            $res = $instance->doSql($sql);
            $arr = array();
            if($res){
                foreach ($res as $count){
                    $arr[$count['aid']] = $count;
                }
            }
            //入redis
            foreach ($notexist_key as $key => $aid){
                if(isset($arr[$aid])){
                    Comm_Redis_Redis::setex($redis, $key, $config_cache['expire'], json_encode($arr[$aid]));
                }else{
                    $arr[$aid] = array(
                        'aid' => $aid,
                        'read_num' => 0,
                        'comment_num' => 0,
                        'collect_num' => 0,
                        'favor_num' => 0
                    );
                    Comm_Redis_Redis::setex($redis, $key, $config_cache['expire'], json_encode($arr[$aid]));
                }
            }
            $exist = $exist + $arr;
        }
        $action_count = array();
        foreach ($aids as $aid){
            $action_count[$aid] = $exist[$aid];
        }

        return $action_count;
    }
    
    /*
     * 获取文章转评赞
     */
    public static function getArticleActionCount($aid){
        if(!is_numeric($aid) || $aid <= 0){
            return false;
        }
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.article.action_count');
        $action_count = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $aid)), true);
        if(empty($action_count)){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $action_count = $instance->field('aid, read_num, comment_num, collect_num, favor_num')->where(array('aid' => $aid))->limit(1)->select('article_action_count')[0];
            if(empty($action_count)){
                $action_count = array(
                    'aid' => $aid,
                    'read_num' => 0,
                    'comment_num' => 0,
                    'collect_num' => 0,
                    'favor_num' => 0
                );
            }
            //入redis
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $aid), $config_cache['expire'], json_encode($action_count));
        }
        return $action_count;
    }
    
    /*
     * 文章类型
     */
    public static function getArticleTypes(){
        //先取redis
        $article_type = array();
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.article.article_type');
        $article_type = json_decode(Comm_Redis_Redis::get($redis, $config_cache['key']), true);
        if(empty($article_type)){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $type = $instance->field('a_t_id, name')->select('article_type');
            if($type){
                foreach ($type as $value){
                    $article_type[$value['a_t_id']] = $value['name'];
                }
                //入redis
                Comm_Redis_Redis::setex($redis, $config_cache['key'], $config_cache['expire'], json_encode($article_type));
            }
        }
        return $article_type;
    }
    
    /*
     * 文章格式
     */
    public static function getArticleForms(){
        //先取redis
        $article_form = array();
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.article.article_form');
        $article_form = json_decode(Comm_Redis_Redis::get($redis, $config_cache['key']), true);
        if(empty($article_form)){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $form = $instance->field('a_f_id, name')->select('article_form');
            if($form){
                foreach ($form as $value){
                    $article_form[$value['a_f_id']] = $value['name'];
                }
                //入redis
                Comm_Redis_Redis::setex($redis, $config_cache['key'], $config_cache['expire'], json_encode($article_form));
            }
        }
        return $article_form;
    }
    
    /*
     * 获取文章基本信息
     */
    public static function getArticleBaseInfo($aid){
        if(!is_numeric($aid) || $aid <= 0){
            return false;
        }
        //先取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.article.content');
        $article = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $aid)), true);
        if(empty($article)){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $article = $instance->field('*')->where(array('aid' => $aid))->limit(1)->select('article')[0];
            if($article){
                //入redis
                Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $aid), $config_cache['expire'], json_encode($article));
            }
        }
        //获取文章格式
        $article_form = self::getArticleForms();
        $article['form_name'] = $article_form[$article['a_f_id']];
        //获取文章类型
        $article_type = self::getArticleTypes();
        $article['type_name'] = $article_type[$article['a_t_id']];
        return $article;
    }
    
    /*
     * 获取文章图片
     */
    public static function getArticleImg($aid, $start = 0, $pagesize = 10){
        if(!is_numeric($aid) || $aid <= 0){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('article.article.image_info');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $imageInfo = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $aid)), true);

        if(! $imageInfo){ //从db中取
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $info = $instance->field('*')->where("aid = $aid")->select('article_images');
            $imageInfo = array();
            if(! empty($info)){
                foreach ($info as $v){
                    $imageInfo[$v['a_i_id']] = $v;
                }
            }else{
                $imageInfo = array();
            }
            //入redis
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $aid), $config_cache['expire'], json_encode($imageInfo));
        }

        return array_slice($imageInfo, $start, $pagesize);
    }
    
    /*
     * 发表文章评论
     */
    public static function setArticleComment(&$data){
        if(empty($data)){
            return false;
        }
        //从发号器中获取评论a_c_id
        $a_c_id = IndexmakerModel::makeIndex(7);
        if(empty($a_c_id)){
            return false;
        }
        $field = array(
            'a_c_id'   => $a_c_id,
            'aid'      => $data['aid'],
            'uid'      => $data['uid'],
            'content'  => $data['content'],
            'atime'    => $data['atime'],
            'ctime'    => $data['ctime'],
            'status'   => 0
        );
        //入redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.article.comment');
        Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $a_c_id), $config_cache['expire'], json_encode($field));
        //入db
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $instance->insert('article_comment', $field);
        //更新文章的转评赞数
        self::articleActionCountAdd($data['aid'], 1);
        //更新文章评论列表
        self::updateArticleCommentList($data['aid'], $a_c_id);
        return 1;
    }
    
    /*
     * 更新文章评论列表
     * a_c_id 评论id
     */
    public static function updateArticleCommentList($aid, $a_c_id){
        if(empty($aid) || empty($a_c_id)){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.article.comment.list');
        return Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $aid), time(), $a_c_id);
    }
    
    /*
     * 更新文章转评赞数
     * type  0 点赞， 1 评论
     */
    public static function articleActionCountAdd($aid, $type = 1){
        if(empty($aid)){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('article.article.action_count');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $article_action_count = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $aid)), true);
        if($article_action_count){  //更新缓存
            switch ($type){
                case 0 :
                    $article_action_count['favor_num']++;
                    break;
                case 1 :
                    $article_action_count['comment_num']++;
                    break;
            }
            //入redis
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $aid), $config_cache['expire'], json_encode($article_action_count));
        }
        
        //更新数据库  （以后可以优化 异步）
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        if($type == 0){
            $sql = 'insert into article_action_count (aid, favor_num) values ('.$aid.', 1) on duplicate key update favor_num = favor_num + 1';
        }elseif($type == 1){
            $sql = 'insert into article_action_count (aid, comment_num) values ('.$aid.', 1) on duplicate key update comment_num = comment_num + 1';
        }
        return $instance->doSql($sql);
    }
    
    /*
     * 发表回复
     */
    public static function setArticleCommentReply(&$data){
        if(empty($data)){
            return false;
        }
        //从发号器中获取回复a_c_r_id
        $a_c_r_id = IndexmakerModel::makeIndex(8);
        if(empty($a_c_r_id)){
            return false;
        }
        $field = array(
            'a_c_r_id'   => $a_c_r_id,
            'aid'        => $data['aid'],
            'uid'        => $data['uid'],
            'touid'      => $data['touid'],
            'a_c_id'     => $data['a_c_id'],
            'f_a_c_r_id' => $data['a_c_r_id'],
            'content'    => $data['content'],
            'atime'      => $data['atime'],
            'ctime'      => $data['ctime'],
            'status'     => 0
        );
        //入redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.article.comment.reply');
        Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $a_c_r_id), $config_cache['expire'], json_encode($field));
        //入db
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $instance->insert('article_comment_reply', $field);
        
        //更新评论的评赞数
        self::articleCommentActionCountAdd($data['a_c_id'], 1);
        //更新评论的回复论列表
        self::updateArticleCommentReplyList($data['a_c_id'], $a_c_r_id);
        return 1;
    }
    
    /*
     * 更新评论的评赞数
     * type  0 点赞， 1 评论
     */
    public static function articleCommentActionCountAdd($a_c_id, $type = 1){
        if(empty($a_c_id)){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('article.article.comment.action_count');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $comment_action_count = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $a_c_id)), true);
        if($comment_action_count){  //更新缓存
            switch ($type){
                case 0 :
                    $comment_action_count['favor_num']++;
                    break;
                case 1 :
                    $comment_action_count['reply_num']++;
                    break;
            }
            //入redis
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $a_c_id), $config_cache['expire'], json_encode($comment_action_count));
        }
        
        //更新数据库  （以后可以优化 异步）
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        if($type == 0){
            $sql = 'insert into article_comment_action_count (a_c_id, favor_num) values ('.$a_c_id.', 1) on duplicate key update favor_num = favor_num + 1';
        }elseif($type == 1){
            $sql = 'insert into article_comment_action_count (a_c_id, reply_num) values ('.$a_c_id.', 1) on duplicate key update reply_num = reply_num + 1';
        }
        return $instance->doSql($sql);
    }
    
    /*
     * 更新评论的回复论列表
     */
    public static function updateArticleCommentReplyList($a_c_id, $a_c_r_id){
        if(empty($a_c_id) || empty($a_c_r_id)){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.article.comment.reply.list');
        return Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $a_c_id), time(), $a_c_r_id);
    }
    
    /*
     * 点赞（文章 + 评论）
     */
    public static function favor(&$data){
        if(empty($data)){
            return false;
        }
        
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        
        if($data['type'] == 1){ //给文章点赞
            //入redis
            $config_cache = Comm_Config::getIni('article.article.favor');
            $a = Comm_Redis_Redis::zrank($redis, sprintf($config_cache['key'], $data['aid']), $data['uid']);
            if($a !== false){ //用户已赞
                return -1;
            }
            Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $data['aid']), time(), $data['uid']);
            //点赞数 +1
            self::articleActionCountAdd($data['aid'], 0);
            //入db
            $field = array(
                'aid'   => $data['aid'],
                'uid'   => $data['uid'],
                'atime' => $data['atime'],
                'ctime' => $data['ctime']
            );
            $res = $instance->insert('article_favor_log', $field);
        
        }elseif($data['type'] == 2){ //给评论点赞
            //入redis
            $config_cache = Comm_Config::getIni('article.article.comment.favor');
            $a = Comm_Redis_Redis::zrank($redis, sprintf($config_cache['key'], $data['a_c_id']), $data['uid']);
            if($a !== false){ //用户已赞
                return -1;
            }
            Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $data['a_c_id']), time(), $data['uid']);
            //点赞数 +1
            self::articleCommentActionCountAdd($data['a_c_id'], 0);
            //入db
            $field = array(
                'a_c_id'   => $data['a_c_id'],
                'uid'   => $data['uid'],
                'atime' => $data['atime'],
                'ctime' => $data['ctime']
            );
            $res = $instance->insert('article_comment_favor_log', $field);
        
        }
        
        return $res;
    }
    
    
    /*
     * 获取文章评论
     */
    public static function getArticleComment($aid, $start = 0, $pagesize = 10){
        if(empty($aid)){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.read');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        //从redis中获取回帖的顺序
        $config_cache = Comm_Config::getIni('article.article.comment.list');
        $list = Comm_Redis_Redis::zrange($redis, sprintf($config_cache['key'], $aid), $start, $start + $pagesize - 1);
        if(empty($list)){
            return false;
        }else{ //取回帖的详细信息
            return self::getCommentDetail(array_keys($list));
        }
    }
    
    /*
     * 评论详情
     * a_c_ids 评论id数组  array(1,2,3)
     */
    public static function getCommentDetail(array $a_c_ids){
        if(empty($a_c_ids)){
            return false;
        }
        $list = self::getCommentBaseInfo($a_c_ids);
        if(empty($list)){
            return false;
        }
        //获取有回复的评论id
        foreach ($list as $key => $value){
            if($value['reply_num'] > 0){
                //获取评论的回复列表
                $arr = self::getReply($value['a_c_id'], 0, 2);
                if($arr){
                    $list[$key]['reply'] = array_values($arr);
                }
            }
        }
        return $list;
    }
    
    /*
     * 获取评论的回复列表
     */
    public static function getReply($a_c_id, $start = 0, $pagesize = 10){
        if(empty($a_c_id)){
            return false;
        }
        //从redis中获取回复的顺序
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('article.article.comment.reply.list');
        $list = Comm_Redis_Redis::zrange($redis, sprintf($config_cache['key'], $a_c_id), $start, $start + $pagesize - 1);
        if(! empty($list)){ //取回复的基本信息
           return self::getReplyBaseInfo(array_keys($list));
        }
        return false;
        
    }
    
    /*
     * 回复的基本信息
     * a_c_r_ids 回复的id数组 array(1,2,3)
     */
    public static function getReplyBaseInfo(array $a_c_r_ids){
        if(empty($a_c_r_ids)){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('article.article.comment.reply');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $keys = array();
        $exsist = array();
        $notexsist = array();
        foreach ($a_c_r_ids as $a_c_r_id){
            $keys[sprintf($config_cache['key'], $a_c_r_id)] = $a_c_r_id;
        }
        
        $list = Comm_Redis_Redis::mget($redis, array_keys($keys));
        foreach ($list as $k => $v){
            empty($v) ? $notexsist[] = $keys[$k] : $exsist[$keys[$k]] = json_decode($v, true) ;
        }
        
        if(count($notexsist) > 0){ //从db中取
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $sql = 'select * from article_comment_reply where a_c_r_id in ('.implode(',', $notexsist).')';
            $res = $instance->doSql($sql);
            if($res){
                foreach ($res as $k => $v){
                    //将数据如redis
                    $a_c_r_id = $v['a_c_r_id'];
                    Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $a_c_r_id), $config_cache['expire'], json_encode($v));
                    $exsist[$a_c_r_id] = $v;
                }
            }
        }
        return $exsist;
    }
    
    
    /*
     * 获取评论的基本信息
     * a_c_ids 评论id数组  array(1,2,3)
     */
    public static function getCommentBaseInfo(array $a_c_ids){
        if(empty($a_c_ids)){
            return false;
        }
        
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('article.article.comment');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $keys = array();
        $exsist = array();
        $notexsist = array();
        foreach ($a_c_ids as $a_c_id){
            $keys[sprintf($config_cache['key'], $a_c_id)] = $a_c_id;
        }
        $list = Comm_Redis_Redis::mget($redis, array_keys($keys));
        foreach ($list as $k => $v){
            empty($v) ? $notexsist[] = $keys[$k] : $exsist[$keys[$k]] = json_decode($v, true) ;
        }
        
        if(count($notexsist) > 0){ //从db中取
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $sql = 'select a_c_id, aid, uid, content, atime, ctime, status from article_comment where a_c_id in ('.implode(',', $notexsist).')';
            $res = $instance->doSql($sql);
            $arr = array();
            if($res){
                foreach ($res as $value){
                    $arr[$value['a_c_id']] = $value; 
                }
            }
            foreach ($notexsist as $a_c_id){
                $m = array();
                if(isset($arr[$a_c_id])){
                    $m = $arr[$a_c_id];
                }else{
                    $m = array();
                }
                $exsist[$a_c_id] = $m;
                Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $a_c_id), $config_cache['expire'], json_encode($m));
            }
            
        }
        //获取评论的转评赞数
        foreach ($exsist as $a_c_id => $value){
            if(empty($value)){
                $exsist[$a_c_id]['reply_num'] = 0;
                $exsist[$a_c_id]['favor_num'] = 0;
                continue;
            }
            $action_count = self::getCommentActionCount($a_c_id);
            $exsist[$a_c_id]['reply_num'] = $action_count['reply_num'];
            $exsist[$a_c_id]['favor_num'] = $action_count['favor_num'];
        }
        
        return $exsist;
    }
    
    /*
     * 获取点赞信息
     * type 1 文章的点赞信息 ， 2 评论的点赞信息
     */
    public static function getFavor($id, $type = 1, $start = 0, $pagesize = 10){
        if(empty($id) || empty($type)){
            return false;
        }
    
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.read');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
    
        if($type == 1){ //帖子
            $config_cache = Comm_Config::getIni('article.article.favor');
        }elseif($type == 2){ //回帖
            $config_cache = Comm_Config::getIni('article.article.comment.favor');
        }
        $favor = Comm_Redis_Redis::zrange($redis, sprintf($config_cache['key'], $id), $start, $pagesize);
        return array_keys($favor);
    }

    /*
     * 举报
     */
    public static function complain(&$data){
        if(empty($data['aid']) || empty($data['uid'])){
            return false;
        }
        $field = array(
            'uid' => $data['uid'],
            'aid' => $data['aid'],
            'content' => $data['content'],
            'atime' => $data['atime'],
            'ctime' => $data['ctime']
        );
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        return $instance->insert('article_complain', $field);
    }
    
}


