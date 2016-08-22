<?php
/**
 * 数据库配置信息
 */

return array(
    'gameinfo' => array(  //库名

        'write' => array(
            'host'   => '139.129.36.196',
            'port'   => 3306,
            'user'   => 'test',
            'passwd' => '123qwe',
            'dbname' => 'gameinfo'
        ),
        'read'  => array(
            'host'   => '139.129.36.196',
            'port'   => 3306,
            'user'   => 'test',
            'passwd' => '123qwe',
            'dbname' => 'gameinfo'
        ),

        /*
        'write' => array(
            'host'   => 'vps.master.youqu.lan',
            'port'   => 3306,
            'user'   => 'youqu',
            'passwd' => 'l*JxbrYw&z',
            'dbname' => 'youqutest'
        ),
        'read'  => array(
            'host'   => 'vps.slave.youqu.lan',
            'port'   => 3306,
            'user'   => 'youqu',
            'passwd' => 'l*JxbrYw&z',
            'dbname' => 'youqutest'
        ),
        */
    ),
);
