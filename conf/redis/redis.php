<?php
/**
 * redis 配置信息
 */

return array(
    'redis1' => array(  //库名
        'write' => array(
            'host'   => $_SERVER['REDIS1_HOST'],
            'port'   => $_SERVER['REDIS1_HOST_PORT'],
        ),
        'read'  => array(
            'host'   => $_SERVER['REDIS1_HOST_R'],
            'port'   => $_SERVER['REDIS1_HOST_PORT_R'],
        ),
    ),
);
