<?php

/**
 * 发号器
 */

class IndexmakerModel {
    
    private static $db = 'gameinfo';  //库名
    
    /*
     * module  业务号  1 用户，2 帖子，3 帖子图片，4 回帖，5 回帖图片，6 回帖的回复/回帖回复的回复，7 文章评论，8 文章评论的回复/文章评论回复的回复
     */
    public static function makeIndex($module, $length = 1){
        if(empty($module)){
            return false;
        }
        $index = null;
        //获取数据库配置文件
        $config = Comm_Config::getPhpConf('db/db.'.self::$db.'.write');
        $instance = Comm_Db_Handler::getInstance(self::$db, $config);
        $instance->startTrans();
        $index = $instance->field('index_add')->where(array('module' => $module))->limit(1)->select('index_maker')[0]['index_add'];
        if(empty($index) || $index < 0){
            $index = 0;
        }
        $index = $index + $length;
        $sql = 'replace into index_maker (module, index_add) values ('.$module.', '.$index.')';
        $res = $instance->doSql($sql);
        if(! $res){
            $index = null;
            $instance->rollback();
            return false;
        }
        $instance->commit();
        return $index;
    }
}






