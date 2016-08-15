<?php
/**
 * 游戏
 * shaohua
 */

class Game_GameModel {
    
    private static $db = 'gameinfo';  //库名

    /*
     * 用户申请游戏
     */
    public static function userApply(&$data){
        if(empty($data)){
            return false;
        }
        $field = array(
            'uid'       => $data['uid'],
            'game_name' => $data['gameName'],
            'atime'     => $data['atime'],
            'ctime'     => $data['ctime']
        );
        //入db
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        return $instance->insert('game_user_apply', $field);
    }

    /*
     * 获取游戏推荐列表
     */
    public static function getRecommend(){
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.game.game_recommend');
        $games = json_decode(Comm_Redis_Redis::get($redis, $config_cache['key']), true);
        if($games === null){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $games = $instance->field('gid, url')->where(array('is_recommend' => 1))->order(array('recommend_index' => 'asc'))->select('game');
            if(!$games){
                $games = array();
            }
            //入redis
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $uid), $config_cache['expire'], json_encode($games));
        }

        return $games;
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
     * 游戏搜索
     */
    public static function search($keywords){
        if(empty($keywords)){
            return false;
        }
        $item = array();
        //获取所有游戏列表
        $games = self::getGameList();
        if($games){
            foreach ($games as $game){
                $patch = stripos($game['name'], $keywords);
                if($patch !== false){
                    $item[] = $game;
                }
            }
        }
        return $item;
    }

    /*
     * 添加用户游戏
     * type 1 来自引导推荐， 2 来自选择游戏
     * gids 游戏id  多个用 逗号 隔开
     */
    public static function userGameAdd($type, $uid, $gids, $atime = '', $ctime = 0){
        if(empty($type) || empty($uid) || empty($gids)){
            return false;
        }
        $list = explode(',', self::getGameListByUid($uid));
        if($type == 1){ //来自引导推荐
            if(in_array($gids, $list)){
                return -1;
            }
            $list[] = $gids;
            $list = implode(',', $list);
        }elseif($type == 2){ //来自选择游戏
            //直接将gids覆盖原数据
            $list = $gids;
        }
        //更新用户游戏列表
        self::updateUserGameList($uid, $list);

        return 1;
    }

    /*
     * 更新用户游戏列表
     * 游戏id  多个用 逗号 隔开
     */
    public static function updateUserGameList($uid, $gids){
        if(empty($uid) || empty($gids)){
            return false;
        }
        //更新redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.game.user.game_list');
        Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $uid), $config_cache['expire'], $gids);
        //更新db
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $sql = 'insert into user_games (uid, gid_list) values ('.$uid.', "'.$gids.'") on duplicate key update gid_list = "'.$gids.'"';
        return $instance->doSql($sql);
    }
    
    /*
     * 获取单个用户的游戏列表
     */
    public static function getGameListByUid($uid){
        if(!is_numeric($uid) || $uid <= 0){
            return false;
        }
        $games = '';
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.game.user.game_list');
        $games = Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $uid));
        if($games === false){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $games = $instance->field('gid_list')->where(array('uid' => $uid))->limit(1)->select('user_games')[0]['gid_list'];
            if(!$games){
                $games = '';
            }
            //入redis
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $uid), $config_cache['expire'], $games);
        }
        return $games;
    }
    
    /*
     * 获取游戏列表
     */
    public static function getGameList(){
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.game.game_list');
        $games = json_decode(Comm_Redis_Redis::get($redis, $config_cache['key']), true);
        if(empty($games)){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $sql = 'select a.gid, a.name, a.url, a.g_t_id, b.g_t_name from game a inner join game_type b on a.g_t_id = b.g_t_id where a.status = 0';
            $res = $instance->doSql($sql);
            //存入redis
            if($res){
                //格式化
                foreach ($res as $value){
                    $games[$value['gid']] = $value;
                }
                Comm_Redis_Redis::setex($redis, $config_cache['key'], $config_cache['expire'], json_encode($games));
            }
        }
        return $games;
    }
    
}