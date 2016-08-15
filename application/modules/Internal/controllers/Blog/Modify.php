<?php
/**
 * 管理员操作（置顶，加精，删除）
 * shaohua
 */

class Blog_ModifyController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['g_g_id']        = (int) Comm_Context::form('g_g_id', 0);
        $this->param['bid']           = (int) Comm_Context::form('bid', 0);  //帖子bid
        $this->param['uid']           = (int) Comm_Context::form('uid', 0);
        $this->param['is_own']        = (int) Comm_Context::form('is_own', 0);  // 1 用户自己，0否
        $this->param['atime']         = Comm_Context::form('atime', date('Y-m-d H:i:s'));
        $this->param['ctime']         = Comm_Context::form('ctime', time());
        $this->param['type']          = (int)Comm_Context::form('type', 0); //1 更新标题， 2 更新正文，3删除图片，4更新图片，5上传图片        
        
        
        //参数检查
        if($this->checkParam()){
            if($this->param['type'] == 1 || $this->param['type'] == 2){
                $this->param['content']       = Comm_Context::form('content', '');
                $res = Blog_BlogModel::modify($this->param['g_g_id'], $this->param['uid'], $this->param['bid'], $this->param['type'], $this->param['content'], $this->param['atime'], $this->param['is_own']);
            }elseif($this->param['type'] == 3){
                $this->param['imgId_del']     = Comm_Context::form('imgId_del', ''); //多个图片id用 ‘_’ 下划线分割
                $res = Blog_BlogModel::delBlogImg($this->param['g_g_id'], $this->param['uid'],$this->param['bid'], array_unique(explode('_', $this->param['imgId_del'])), $this->param['atime'], $this->param['is_own']);
            }elseif($this->param['type'] == 4){
                $this->param['imgId_mod']     = Comm_Context::form('imgId_mod', 0); //要修改的图片id
                $this->param['img_mod']       = base64_decode(Comm_Context::form('img_mod', ''));  //图片流 base64
                $this->param['img_mod_ext']   = Comm_Context::form('img_mod_ext', '');  //图片扩展名
                
                $format = Comm_Config::getIni('sprintf.blog.image.name'); //图片名称格式
                $img_name = sprintf($format, $this->param['uid'], rand(), time(), rand(), $this->param['img_mod_ext']);
                $file = date('Y/m/d').'/'.$img_name;
                //生成多张图片 原图 正文图 缩略图
                $filepath0 = '/blog/origin/'.$file;
                //$filepath1 = '/blog/normal/'.$file;
                //$filepath2 = '/blog/thumbnail/'.$file;
                $filename0 = IMG_PATH.$filepath0;
                //$filename1 = IMG_PATH.$filepath1;
                //$filename2 = IMG_PATH.$filepath2;
                ImagedealModel::imageWrite($filename0, $this->param['img_mod']);
                if(is_file($filename0)){  //说明图片成功上传
                    $img_url = STATIC_SERVER.$filepath0;
                }else{ //网络异常
                    $this->format(3);
                    $this->jsonResult($this->data);
                    return $this->end();
                }
                
                //更新帖子图片信息
                $res = Blog_BlogModel::updateBlogImage($this->param['g_g_id'], $this->param['uid'],$this->param['bid'], $this->param['imgId_mod'], $img_url, $this->param['atime'], $this->param['is_own']);
            }elseif($this->param['type'] == 5){
                $this->param['img_num'] = Comm_Context::form('img_num', 0); //新上传图片属性
                
                if($this->param['img_num'] >= 1){
                    $img_urls = array();
                    for($i = 0; $i < $this->param['img_num']; $i++){
                        $key = 'img_'.$i;
                        $key_ext = 'img_ext_'.$i;
                        $img = base64_decode(Comm_Context::form($key));  //base64 转码图片流
                        $img_ext = Comm_Context::form($key_ext);
                        $format = Comm_Config::getIni('sprintf.blog.image.name'); //图片名称格式
                        $img_name = sprintf($format, $this->param['uid'], rand(), time(), rand(), $img_ext);
                        $file = date('Y/m/d').'/'.$img_name;
                        //生成多张图片 原图 正文图 缩略图
                        $filepath0 = '/blog/origin/'.$file;
                        //$filepath1 = '/blog/normal/'.$file;
                        //$filepath2 = '/blog/thumbnail/'.$file;
                        $filename0 = IMG_PATH.$filepath0;
                        //$filename1 = IMG_PATH.$filepath1;
                        //$filename2 = IMG_PATH.$filepath2;
                        ImagedealModel::imageWrite($filename0, $img);
                        if(is_file($filename0)){  //说明图片成功上传
                            $img_urls[] = STATIC_SERVER.$filepath0;
                        }else{ //网络异常
                            $this->format(3);
                            $this->jsonResult($this->data);
                            return $this->end();
                        }
                    }
                    //发号器   图片url入库
                    $b_i_id_end = IndexmakerModel::makeIndex(3, $this->param['img_num']);
                    if(! $b_i_id_end){//网络异常
                        $this->format(3);
                        $this->jsonResult($this->data);
                        return $this->end();
                    }
                    $b_i_id_start = $b_i_id_end - $this->param['img_num'] + 1;
                    $urls = array();
                    foreach ($img_urls as $url){
                        $urls[$b_i_id_start] = $url;
                        $b_i_id_start++;
                    }
                    
                    //拼接images的redis缓存
                    $image_info = array();
                    foreach ($urls as $b_i_id => $url){
                        $image_info[$b_i_id] = array(
                            'b_i_id'  => $b_i_id,
                            'bid'     => $this->param['bid'],
                            'url_0'   => '',
                            'url_1'   => '',
                            'url_2'   => $url,
                            'atime'   => $this->param['atime'],
                            'ctime'   => $this->param['ctime'],
                            'summary' => '',
                            'status'  => 0
                        );
                    }
                    $before = Blog_BlogModel::getBlogImage($this->param['bid']);
                    $image_info = $before + $image_info;
                    Blog_BlogModel::setBlogImage($image_info);
                    $res = Blog_BlogModel::insertBlogImage($this->param['bid'], $urls);
                    if(! $res){//网络异常
                        $this->format(3);
                        $this->jsonResult($this->data);
                        return $this->end();
                    }
                    //更新帖子的图片数量
                    $before_num = Blog_BlogModel::getBlogDetail($this->param['bid'])['pic_num'];
                    Blog_BlogModel::updateBlog($this->param['bid'], 'pic_num', $before_num + $this->param['img_num']);
                    
                }
            }
            
            if($res == 1){
                $this->format(0);
            }elseif($res == -1){
                $this->format(5);
            }else{
                $this->format(3);
            }
            
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['bid']) || empty($this->param['uid']) || empty($this->param['type'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}
