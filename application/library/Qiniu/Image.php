<?php
/**
 * 图片处理
 * User: shaohua
 */

require_once __DIR__ .'/autoload.php';
use Qiniu\Auth;

class Qiniu_Image{
    // host
    public static $host = 'http://ob0cyhgjg.bkt.clouddn.com';
    // 公钥
    private static $accessKey = 'GJ-HG2w9rPYReDKMRyMn0c8smDnFl65bEy2s9g8m';
    // 私钥
    private static $secretKey = 'A53dZYQkAFpPh2ovUiv16g3i5yG_sJss8cuR2aa4';
    // 空间名
    private static $bucket = 'youqu';
    // 图片缩放时使用的队列名称。
    private static $pipeline = 'image_deflate_list';
    // 缩略图尺寸
    private static $size_1 = 'imageView/2/w/100/h/100';
    // 正文图尺寸
    private static $size_2 = 'imageView/2/w/200/h/200';
    /*
     * 获取token
     * name1, name2  缩略图名称
     */
    public static function getToken($name1, $name2){

        // 初始化签权对象
        $auth = new Auth(self::$accessKey, self::$secretKey);

        //缩略图的key名
        $key1 = Qiniu\base64_urlSafeEncode(self::$bucket.':'.$name1);
        $key2 = Qiniu\base64_urlSafeEncode(self::$bucket.':'.$name2);

        // 缩略图处理命令格式
        $format = '%s|saveas/%s;%s|saveas/%s';
        $fops = sprintf($format, self::$size_1, $key1, self::$size_2, $key2);

        $policy = array(
            'persistentOps' => $fops,
            'persistentPipeline' => self::$pipeline,
            //'returnUrl' => 'http://www.baidu.com' //用于web的form表单提交后的跳转页面
        );

        // 生成上传 Token
        $token = $auth->uploadToken(self::$bucket, null, 3600, $policy);

        return $token;
    }
}