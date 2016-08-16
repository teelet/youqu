<?php
/**
 * redis 配置信息
 */

return array(
    'redis1' => array(  //库名

        'write' => array(
            'host'   => '139.129.36.196',
            'port'   => 6379
        ),
        'read'  => array(
            'host'   => '139.129.36.196',
            'port'   => 6379
        ),

        /*
        'write' => array(
            'host'   => 'vps.redis.youqu.lan',
            'port'   => 6379
        ),
        'read'  => array(
            'host'   => 'vps.redis.youqu.lan',
            'port'   => 6379
        ),
        */
    ),
);
