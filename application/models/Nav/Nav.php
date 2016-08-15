<?php
/**
 * 导航信息
 */

class Nav_NavModel{

    private static $db = 'gameinfo';  //库名

    /*
     * 顶导
     */
    public static function getNav(){
        $nav = $config_redis = Comm_Config::getPhpConf('nav/category.mobile');
        if($nav){
            return $nav;
        }else{
            return false;
        }
    }

}