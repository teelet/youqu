<?php

class Comm_Db_Handler {
    
    private static $instances = array();
    
    /**
     * @param $DbName 库名
     * @param $config 配置文件 形如： array('host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'passwd' => '111111', 'dbname' => 'test');
     * @return Ambigous <multitype:, Comm_Db_Mysql>
     */
    public static function getInstance($DbName, $config){
        if(isset(self::$instances[$DbName])){
            return self::$instances[$DbName];
        }else{
            self::$instances[$DbName] = new Comm_Db_Mysql($config);
            return self::$instances[$DbName];
        }
    }
    
}