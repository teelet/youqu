<?php
/**
 * 获取图片上传token
 * shaohua
 */

class Home_GetimagetokenController extends AbstractController {

    public function indexAction() {
        //参数检查
        $this->param['uid'] = (int) Comm_Context::form('uid', 0);
        $this->param['pic_num'] = (int) Comm_Context::form('pic_num', 0);
        if($this->checkParam()){
            $token_arr = array();
            for($i = 1; $i <= $this->param['pic_num']; $i++){
                $pic_origin_name = Comm_Context::form("pic_name_$i", '');
                $pic_ext = end(explode('.', $pic_origin_name));
                //重生成唯一name
                $format = Comm_Config::getIni('sprintf.image.image.name'); //图片名称格式
                $pic_name_prefix = sprintf($format, $this->param['uid'], rand(), time(), rand());
                $pic_name_0 = $pic_name_prefix.'_0.'.$pic_ext;  //缩略图
                $pic_name_1 = $pic_name_prefix.'_1.'.$pic_ext;  //正文图
                $pic_name_2 = $pic_name_prefix.'_2.'.$pic_ext;  //原图
                $token = Qiniu_Image::getToken($pic_name_0, $pic_name_1); //获取token,当需要生成缩略图时,传缩略图名称
                $token_arr[] = array(
                    'pic_origin_name' => $pic_origin_name,  //参数传递过来的原图
                    'pic_name'        => $pic_name_2, //云存储上的原图名
                    'token'           =>  $token
                );
            }

            if($token_arr){
                $this->format(0);
                $this->data['results'] = $token_arr;
            }else{
                $this->format(3);
            }

        }
        $this->jsonResult($this->data);
        return $this->end();
    }

    public function checkParam(){
        if(empty($this->param['pic_num']) || empty($this->param['uid'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }

}
