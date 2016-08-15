<?php
/**
 * 接口错误信息配置
 */

return array(
    'statusCode' => array(
        'success' => 0, //成功
        'error'   => 1, //失败
    ),
    'message'    => array(
        'successMsg'        => '操作成功',
        'errorMsg'          => '操作失败',
        'paramMsg'          => '参数有误',
        'netMsg'            => '网络异常',
        'againMsg'          => '重复操作',
        'limitMsg'          => '次数限制',
        'checkCodeErrMsg'   => '验证码错误',
        'checkCodeDeadMsg'  => '验证码过期',
    )
);