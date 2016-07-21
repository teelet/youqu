<?php

/**
 * 获取配置文件
 */

class Comm_Config {
    
    private static $inst = array();
    
    public static function getIni($key){
        try {
            if (strpos($key, '.') !== false) {
                list($file, $path) = explode('.', $key, 2);
            }else{
                $file = $key;
            }
            $config_file = APPLICATION_PATH . '/conf/' . $file . '.ini';
            if (!isset(self::$inst[$file])) {
                self::$inst[$file] = new Yaf_Config_Ini($config_file);
            }
            $config = self::$inst[$file]->toArray();
            if (isset($path)) {
                $arr = explode('.', $path);
                foreach ($arr as $val){
                    $config = $config[$val];
                }
            }
            return $config;
        }catch (Exception $e){
            echo $e->getMessage();
        }
        
    }
}