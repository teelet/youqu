<?php

/**
 *  操作redis
 */

class Comm_Redis_Redis {
    
    private static $inst = array();
    
    //连接 redis
    public static function connect($host, $port) {
        try{
            $index = sprintf('redis_%s_%s', $host, $port);
            if(! isset(self::$inst[$index])){
                $redis = new Redis();
                $redis->connect($host, $port);
                self::$inst[$index] = $redis;
            }else{
                $redis = self::$inst[$index];
            }
            return $redis;
        }catch (RedisException $e){
            echo $e->getMessage();
        }
    }
    
    //关闭 redis
    public static function close (Redis $redis){
        try {
            return $redis->close();
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
    }
    
    //判断 key 是否存在
    public static function exists (Redis $redis, $key){
        try {
            return $redis->exists($key);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
    }
    
    //获取值
    public static function get (Redis $redis, $key){
        try {
            return $redis->get($key);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
    }
    
    //设置值
    public static function set (Redis $redis, $key, $value){
        try {
            return $redis->set($key, $value);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
    }
    
    //设置有生命周期的值
    public static function setex (Redis $redis, $key, $second, $value){
        try {
            return $redis->setex($key, $second, $value);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
    }
    
    //批量插入数据 array('key'=>'value','key'=>'value')
    public static function mset (Redis $redis, $values){
        try {
            return $redis->mset($values);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
    }
    
    //批量获取 array('key1', 'key2')
    public static function mget (Redis $redis, $keys){
        try {
            $result = $redis->mget($keys);
            $result = is_array($result) ? @array_combine($keys, $result) : false;
            return $result;
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
    }
    
    //设置key的生命周期
    public static function expire (Redis $redis, $key, $second){
        try {
            return $redis->expire($key, $second);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
    }
    
    public static function ttl(Redis $redis, $key) {
        try {
            $result = $redis->ttl($key);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function decr(Redis $redis, $key) {
        try {
            $result = $redis->decr($key);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function decrBy(Redis $redis, $key, $offset) {
        try {
            $result = $redis->decrBy($key, $offset);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function incr(Redis $redis, $key) {
        try {
            $result = $redis->incr($key);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function incrBy(Redis $redis, $key, $offset) {
        try {
            $result = $redis->incrBy($key, $offset);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function zincrBy(Redis $redis, $key, $member, $offset) {
        try {
            $result = $redis->zIncrBy($key, $offset, $member);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function hincrby(Redis $redis, $key, $field, $offset) {
        try {
            $result = $redis->hincrBy($key, $field, $offset);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function scard(Redis $redis, $key) {
        try {
            $result = $redis->scard($key);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function zscore(Redis $redis, $key, $member) {
        try {
            $result = $redis->zscore($key, $member);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function zrevrank(Redis $redis, $key, $member) {
        $rank = $redis->zrevrank($key, $member);
        return $rank;
    }
    
    public static function zcard(Redis $redis, $key) {
        try {
            $result = $redis->zcard($key);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function zcount(Redis $redis, $key, $min, $max) {
        try {
            $result = $redis->zcount($key, $min, $max);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function zrange(Redis $redis, $key, $start, $end, $withscores = true) {
        try {
            $result = $redis->zRange($key, $start, $end, $withscores);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function zrevrange(Redis $redis, $key, $start, $end, $withscores = true) {
        try {
            $result = $redis->zRevRange($key, $start, $end, $withscores);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function zadd(Redis $redis, $key, $score, $member) {
        try {
            $result = $redis->zadd($key, $score, $member);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function zrank(Redis $redis, $key, $member) {
        try {
            $result = $redis->zrank($key, $member);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function zrem(Redis $redis, $key, $member) {
        try {
            $result = $redis->zrem($key, $member);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function sadd(Redis $redis, $key, $member) {
        try {
            $result = $redis->sadd($key, $member);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function sremove(Redis $redis, $key, $member) {
        try {
            $result = $redis->sremove($key, $member);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function spop(Redis $redis, $key) {
        try {
            $result = $redis->spop($key);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function srandmember(Redis $redis, $key) {
        try {
            $result = $redis->srandmember($key);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function smembers(Redis $redis, $key) {
        try {
            $result = $redis->smembers($key);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function lrange(Redis $redis, $key, $start, $end) {
        try {
            $result = $redis->lrange($key, $start, $end);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function lpush(Redis $redis, $key, $value) {
        try {
            $result = $redis->lpush($key, $value);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function rpush(Redis $redis, $key, $value) {
        try {
            $result = $redis->rpush($key, $value);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function lpop(Redis $redis, $key) {
        try {
            $result = $redis->lpop($key);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function rpop(Redis $redis, $key) {
        try {
            $result = $redis->rpop($key);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function info(Redis $redis) {
        try {
            $result = $redis->info();
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function del(Redis $redis, $key) {
        try {
            $result = $redis->del($key);
        } catch (RedisException $e) {
            echo $e->getMessage();
        }
        return $result;
    }
    
    public static function hashRedis($key) {
        return intval(sprintf('%u', crc32($key)) / 4) % 4;
    }
    
    public static function hashTopRedis($key) {
        return intval(sprintf('%u', crc32($key)) / 8) % 8;
    }
      
}