<?php
/**
 * 数据库配置信息
 */

return array(
    'gameinfo' => array(  //库名
        'write' => array(
            'host'   => $_SERVER['DB1_HOST'],
            'port'   => $_SERVER['DB1_PORT'],
            'user'   => $_SERVER['DB1_USER'],
            'passwd' => $_SERVER['DB1_PASS'],
            'dbname' => $_SERVER['DB1_NAME'],
        ),
        'read'  => array(
            'host'   => $_SERVER['DB1_HOST_R'],
            'port'   => $_SERVER['DB1_PORT_R'],
            'user'   => $_SERVER['DB1_USER_R'],
            'passwd' => $_SERVER['DB1_PASS_R'],
            'dbname' => $_SERVER['DB1_NAME_R'],
        ),
    ),
);
