<?php
/**
 * 游戏社区
 * shaohua
 */

class Group_GamegroupModel {

    private static $db = 'gameinfo';  //库名
    
    /*
     * 签到
     */
    public static function sign(&$data){
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.group.group_user_sign');
        $is = Comm_Redis_Redis::zrank($redis, sprintf($config_cache['key'], $data['g_g_id'], $data['uid']), $data['atime']);
        if($is !== false){ //当天已签到
            return -1;
        }
        Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $data['g_g_id'], $data['uid']), time(), $data['atime']);
        //入db
        $field = array(
            'g_g_id' => $data['g_g_id'],
            'uid'    => $data['uid'],
            'atime'  => $data['atime'],
            'ctime'  => $data['ctime']
        );
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $res = $instance->insert('group_user_sign', $field);
        return $res;
    }
    
    /*
     * 加入社区
     */
    public static function addGameGroup(&$data){
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.user.group_list');
        $is = Comm_Redis_Redis::zrank($redis, sprintf($config_cache['key'], $data['uid']), $data['g_g_id']);
        if($is !== false){ //用户之前已加入过
            return -1;
        }
        //入redis 社区列表
        $config_cache = Comm_Config::getIni('sprintf.group.group_user_list');
        $a = Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $data['g_g_id']), time(), $data['uid']);
        if($a == 0){ //用户之前已加入过
            return -1;
        }
        //更新社区人数列表
        self::gameGroupActionCountAdd($data['g_g_id'], 0);
        //入redis 本人列表
        if($a){
            $config_cache = Comm_Config::getIni('sprintf.user.group_list');
            $b = Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $data['uid']), time(), $data['g_g_id']);
            if($b){//入db
                //获取数据库配置文件
                $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
                $instance = Comm_Db_Handler::getInstance(self::$db, $config);
                $field = array(
                    'uid' => $data['uid'],
                    'g_g_id' => $data['g_g_id'],
                    'atime' => $data['atime'],
                    'ctime' => $data['ctime']
                );
                $res = $instance->insert('user_game_group', $field);
                return $res;
            }
        }
        return false;
    }
    
    /*
     * 获取社区最新成员列表
     */
    public static function getGroupNewUser($g_g_id, $start = 0, $pagesize = 10){
        if(empty($g_g_id)){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.read');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.group.group_user_list');
        $list = Comm_Redis_Redis::zrevrange($redis, sprintf($config_cache['key'], $g_g_id), $start, $start + $pagesize);
        return array_keys($list);
    }
    
    public static function getUserNewGroup($uid, $start = 0, $pagesize = 10){
        if(empty($uid)){
            return false;
        }
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.read');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.user.group_list');
        $list = Comm_Redis_Redis::zrevrange($redis, sprintf($config_cache['key'], $uid), $start, $start + $pagesize);
        return array_keys($list);
    }
    
    /*
     * 获取社区用户数/帖子数
     */
    public static function getGameGroupActionCount($g_g_id){
        if(empty($g_g_id)){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.group.gamegroup.action_count');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $gamegroup_action_count = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $g_g_id)), true);
        if(! $gamegroup_action_count){ //取db
            //获取数据库配置文件
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $gamegroup_action_count = $instance->field('user_num, blog_num')->where('g_g_id = '.$g_g_id)->select('game_group_action_count')[0];
            if($gamegroup_action_count){ //入redis
                Comm_Redis_Redis::set($redis, sprintf($config_cache['key'], $g_g_id), json_encode($gamegroup_action_count));
            }
        }
        return $gamegroup_action_count;
        
    }
    
    
    /*
     * 游戏社区 用户数/帖子数  +1
     * bid
     * type 0 用户+1， 1 帖子+1
     */
    public static function gameGroupActionCountAdd($g_g_id, $type = 0){
        if(! is_numeric($g_g_id) || $g_g_id <= 0){
            return false;
        }
        //先从redis里面取
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $config_cache = Comm_Config::getIni('sprintf.group.gamegroup.action_count');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $gamegroup_action_count = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $g_g_id)), true);
        if($gamegroup_action_count){  //更新缓存
            switch ($type){
                case 0 :
                    $gamegroup_action_count['user_num']++;
                    break;
                case 1 :
                    $gamegroup_action_count['blog_num']++;
                    break;
            }
            //入redis
            Comm_Redis_Redis::set($redis, sprintf($config_cache['key'], $g_g_id), json_encode($gamegroup_action_count));
        }
    
        //更新数据库  （以后可以优化 异步）
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        if($type == 0){
            $sql = 'insert into game_group_action_count (g_g_id, user_num) values ('.$g_g_id.', 1) on duplicate key update user_num = user_num + 1';
        }elseif($type == 1){
            $sql = 'insert into game_group_action_count (g_g_id, blog_num) values ('.$g_g_id.', 1) on duplicate key update blog_num = blog_num + 1';
        }
        $instance->doSql($sql);
    }
    
    /*
     * 获取社区列表
     * g_g_id 游戏社区id  
     * 当传递游戏社区id时 返回该id所属游戏的说有社区
     */
    public static function getGroupList($g_g_id = NULL){
        $data = array();
        //获取游戏列表
        $games = Game_GameModel::getGameList();
        if(empty($games)){
            return false;
        }
        //格式化
        $list = array();
        if($games){
            foreach ($games as $game){
                $list[$game['g_t_id']][] = $game;
            }
                ksort($list);
        }
        $data['gamelist'] = $list;
        
        
        if($g_g_id){ //取g_g_id所属的游戏社区列表
            $group_info = self::getGroupInfo($g_g_id);
            $gid = $group_info['gid'];
            $g_t_id = 0;
            $break = 0;
            foreach ($list as $key => $value){
                foreach ($value as $v){
                    if($gid == $v['gid']){
                        $g_t_id = $key;
                        $break = 1;
                        break;
                    }
                }
                if($break == 1){
                    break;
                }
            }
        }else{ //取默认第一个游戏的社区列表
            $g_t_id = array_keys($list)[0]; 
            $gid = $list[$g_t_id][0]['gid'];
        }
        //获取gid下的游戏社区
        $groups = self::getGroupListByGid($gid, 0, 10);
        $data['grouplist'] = $groups;
        $data['g_t_id'] = $g_t_id;
        $data['gid'] = $gid;
        
        return $data;
        
    }
    
    /*
     * 获取游戏下的游戏社区
     */
    public static function getGroupListByGid($gid, $start = 0, $pagesize = 10){
        if(empty($gid)){
            return false;
        }
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.group.group_list_gid');
        $groups = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $gid)), true);
        if(empty($groups)){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $groups = $instance->field('*')->where('gid = '.$gid)->select('game_group');
            //存入redis
            if($groups){
                //格式化
                Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $gid), $config_cache['expire'], json_encode($groups));
            }
        }
        return array_slice($groups, $start, $start + $pagesize);
        
    }
    
    /*
     * 游戏社区详细
     */
    public static function getGroupInfo($g_g_id){
        if(empty($g_g_id)){
            return false;
        }
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.group.group_info');
        $group_info = json_decode(Comm_Redis_Redis::get($redis, sprintf($config_cache['key'], $g_g_id)), true);
        if(empty($group_info)){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $group_info = $instance->field('*')->where('status = 0 and g_g_id = '.$g_g_id)->limit(1)->select('game_group')[0];
            //存入redis 
            if($group_info){
                //格式化
                Comm_Redis_Redis::setex($redis, sprintf($config_cache['key'], $g_g_id), $config_cache['expire'], json_encode($group_info));
            }
        }
        return $group_info;
    }
    
    /*
     * 获取用户已加入的社区列表
     */
    
    public static function getUserGroups($uid){
        if(empty($uid)){
            return false;
        }
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.user.group_list');
        $list = array_keys(Comm_Redis_Redis::zrange($redis, sprintf($config_cache['key'], $uid), 0, -1));
        if(! empty($list)){
            //取db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $res = $instance->field('g_g_id')->where('uid = '.$uid)->select('user_game_group');
            $a = array();
            if(! empty($res)){ //入 redis
                foreach ($res as $v){
                    $a[] = $v['g_g_id'];
                    Comm_Redis_Redis::zadd($redis, sprintf($config_cache['key'], $uid), time(), $v['g_g_id']);
                }
            }
            $list = $a;
        }
        return $list;
        
    }
    
    /*
     * 获取推荐社区列表
     */
    public static function getRecommendGroup(){
        //取redis
        $config_redis = Comm_Config::getPhpConf('redis/redis.redis1.write');
        $redis = Comm_Redis_Redis::connect($config_redis['host'], $config_redis['port']);
        $config_cache = Comm_Config::getIni('sprintf.group.group.recommend');
        $list = json_decode(Comm_Redis_Redis::get($redis, $config_cache['key']), true);
        if(empty($list)){
            //db
            $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.read');
            $instance = Comm_Db_Handler::getInstance(self::$db, $config);
            $list = $instance->field('g_g_id')->where('is_recommend = 1')->order(array('recommend_index' => 'asc'))->select('game_group');
            if($list){
                $res = array();
                foreach ($list as $value){
                    $res[] = $value['g_g_id'];
                }
                Comm_Redis_Redis::setex($redis, $config_cache['key'], $config_cache['expire'], json_encode($res));
            }
        }
        
        return $list;
    }
    

}


















