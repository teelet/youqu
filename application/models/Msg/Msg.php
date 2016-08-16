<?php
/**
 * 手机短信
 * shaohua
 */

class Msg_MsgModel{

    private static $db = 'gameinfo';  //库名

    /*
     * 发送短信
     */
    public static function sendMsg($uid, $tel){
        if(empty($uid) || empty($tel)){
            return false;
        }
        //随机生成 6位验证码
        $code = rand(100000, 999999);

        //调用短信接口
        $res = Msg_Callsms::sendPostRequest($tel, $code);
        $res = json_decode($res, true);

        if($res['code'] == 1000){ //成功
            //入redis
            $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
            $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
            $config_cache = Comm_Config::getIni('message.checkcode.user_cache');
            Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $uid), $config_cache['expire'], $code);

            return true;
        }else{
            return false;
        }
    }

    /*
     * 校验验证码
     */
    public static function checkCode($uid, $code){
        if(empty($uid) || empty($code)){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.read');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('message.checkcode.user_cache');
        $user_code = Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $uid));
        if(!$user_code){
            return -1;   //过期
        }elseif($user_code == $code){
            return 1; //正确
        }else{
            return 0; //错误
        }
    }
}