<?php

/**
 * 图片处理
 */

class ImagedealModel {
    
    /**
     * 把图片写入文件
     * @param $filename 文件名
     * @param $content  图片内容
     */
    public static function imageWrite($filename, $content){
        $dir = dirname($filename);
        if(! is_dir($dir)){
            mkdir($dir, 0777, true);
            chmod($dir, 0777);
        }
        file_put_contents($filename, $content);
    }
}