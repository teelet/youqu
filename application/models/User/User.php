<?php
/**
 * 用户信息
 * shaohua
 */

class User_UserModel {
    
    private static $db = 'gameinfo';  //库名
    
    /*
     * 用户基本信息
     */
    public static function getUserInfo($uid){
        if(! is_numeric($uid) || $uid <= 0){return  false;}
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.user.userinfo');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $userInfo = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $uid)), true);
        if(! $userInfo){//从db中取
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $userInfo = $instance->field('*')->where("uid = $uid")->limit(1)->select('user')[0];
            if(! empty($userInfo)){
                //入redis
                Comm_Redis_Redis::set($redis, sprintf($config_cache['key'], $uid), json_encode($userInfo));
            }
        }
        
        return $userInfo;
    }
    
    /*
     * 批量获取用户基本信息
     */
    public static function getUserInfos(array $uids){
        if(empty($uids)){return  false;}
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.read');
        $config_cache = Comm_Config::getIni('sprintf.user.userinfo');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $keys = array();
        $exsist = array();
        $notexsist = array();
        foreach ($uids as $uid){
            $keys[sprintf($config_cache['key'], $uid)] = $uid;
        }
        $list = Comm_Redis_Redis::mget($redis, array_keys($keys));
        foreach ($list as $k => $v){
            empty($v) ? $notexsist[] = $keys[$k] : $exsist[$keys[$k]] = json_decode($v, true) ;
        }
        
        if(count($notexsist) > 0){ //从db中取
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $sql = 'select * from user where uid in ('.implode(',', $notexsist).')';
            $res = $instance->doSql($sql);
            if($res){
                foreach ($res as $k => $v){
                    //将数据如redis
                    $uid = $v['uid'];
                    Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $uid), $config_cache['expire'], json_encode($v));
                    $exsist[$uid] = $v;
                }
            }
        }
        
        return $exsist;
    }
}









