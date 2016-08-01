<?php
/**
 * 游戏
 * shaohua
 */

class Game_GameModel {
    
    private static $db = 'gameinfo';  //库名
    
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