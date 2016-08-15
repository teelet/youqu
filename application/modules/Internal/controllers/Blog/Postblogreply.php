<?php
/**
 * 回帖 或 回复
 * shaohua
 */

class Blog_PostblogreplyController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['type']    = (int) Comm_Context::form('type', 0); ////1 回帖， 2 回复
        $this->param['bid']     = (int) Comm_Context::form('bid', 0);  //帖子bid
        $this->param['uid']     = (int) Comm_Context::form('uid', 0);  //用户uid
        $this->param['b_c_id'] = Comm_Context::form('b_c_id', 0); //回帖b_c_id
        $this->param['content'] = Comm_Context::form('content', '');
        $this->param['atime']   = Comm_Context::form('atime', date('Y-m-d H:i:s'));
        $this->param['ctime']   = (int) Comm_Context::form('ctime', time());
        //参数检查
        if($this->checkParam()){
            if($this->param['type'] == 1){ //回帖
                $this->param['pic_num'] = (int) Comm_Context::form('pic_num', 0);
                $this->param['buid']    = (int) Comm_Context::form('buid', 0);  //帖主的uid
                $pic_urls = array();
                if($this->param['pic_num'] >= 1){
                    for($i = 0; $i < $this->param['pic_num']; $i++){
                        $key = 'pic_'.$i;
                        $key_ext = 'pic_ext_'.$i;
                        $pic = base64_decode(Comm_Context::form($key));  //base64 转码图片流
                        $pic_ext = Comm_Context::form($key_ext);
                        $format = Comm_Config::getIni('sprintf.blog.image.name'); //图片名称格式
                        $pic_name = sprintf($format, $this->param['uid'], rand(), time(), rand(), $pic_ext);
                        $file = date('Y/m/d').'/'.$pic_name;
                        //生成多张图片 原图 正文图 缩略图
                        $filepath0 = '/blog/origin/'.$file;
                        //$filepath1 = '/blog/normal/'.$file;
                        //$filepath2 = '/blog/thumbnail/'.$file;
                        $filename0 = IMG_PATH.$filepath0;
                        //$filename1 = IMG_PATH.$filepath1;
                        //$filename2 = IMG_PATH.$filepath2;
                        ImagedealModel::imageWrite($filename0, $pic);
                        if(is_file($filename0)){  //说明图片成功上传
                            $pic_urls[] = STATIC_SERVER.$filepath0;
                        }else{ //网络异常
                            $this->format(3);
                            $this->jsonResult($this->data);
                            return $this->end();
                        }
                    }
                }
                
                //回帖内容入库
                //从发号器中获取预先给定的b_c_id，便于后期异步入库
                $b_c_id = IndexmakerModel::makeIndex(4);
                if(! $b_c_id){//网络异常
                    $this->format(3);
                    $this->jsonResult($this->data);
                    return $this->end();
                }
                $this->param['b_c_id'] = $b_c_id;
                Blog_BlogModel::insertBlogReply($this->param, 1);
                //回帖内容人redis
                Blog_ReplyModel::setBlogCommentBaseInfo($this->param);
                //初始化回帖转评赞数
                Blog_BlogModel::initBlogCommentActionCount($b_c_id);
                
                //回帖图片入库
                if(count($pic_urls) > 0){
                    $b_c_i_id_end = IndexmakerModel::makeIndex(5, $this->param['pic_num']);
                    $b_c_i_id_start = $b_c_i_id_end - $this->param['pic_num'] + 1;
                    if(! $b_c_i_id_end){//网络异常
                        $this->format(3);
                        $this->jsonResult($this->data);
                        return $this->end();
                    }
                    $urls = array();
                    foreach ($pic_urls as $url){
                        $urls[$b_c_i_id_start] = $url;
                        $b_c_i_id_start++;
                    }
                    $res = Blog_BlogModel::insertBlogReplyImage($this->param['bid'], $b_c_id, $urls);
                    
                    if(! $res){//网络异常
                        $this->format(3);
                        $this->jsonResult($this->data);
                        return $this->end();
                    }
                    
                    //拼接images的redis缓存
                    $image_info = array();
                    foreach ($urls as $b_c_i_id => $url){
                        $image_info[$b_c_i_id] = array(
                            'b_c_i_id'  => $b_c_i_id,
                            'bid'       => $this->param['bid'],
                            'b_c_id'    => $b_c_id,
                            'url_0'     => '',
                            'url_1'     => '',
                            'url_2'     => $url,
                            'atime'   => $this->param['atime'],
                            'ctime'   => $this->param['ctime']
                        );
                    }
                    Blog_ReplyModel::setBlogCommentImage($image_info);
                }
                
            }elseif($this->param['type'] == 2){ //回复
                $this->param['f_b_c_c_id'] = Comm_Context::form('b_c_c_id', 0); //被回复的b_c_c_id
                $this->param['touid'] = Comm_Context::form('touid', 0); //被回复的uid
                $b_c_c_id = IndexmakerModel::makeIndex(6);
                if(! $b_c_c_id){//网络异常
                    $this->format(3);
                    $this->jsonResult($this->data);
                    return $this->end();
                }
                $this->param['b_c_c_id'] = $b_c_c_id;
                Blog_BlogModel::insertBlogReply($this->param, 2);
                //拼接回复入redis
                $reply = array(
                    'b_c_c_id'   => $b_c_c_id,
                    'f_b_c_c_id' => $this->param['f_b_c_c_id'],
                    'b_c_id'     => $this->param['b_c_id'],
                    'bid'        => $this->param['bid'],
                    'uid'        => $this->param['uid'],
                    'touid'      => $this->param['touid'],
                    'content'    => $this->param['content'],
                    'atime'      => $this->param['atime'],
                    'ctime'      => $this->param['ctime']
                );
                Blog_ReplyModel::setBlogCommentReplyBaseInfo($reply);
            }
            
            $this->format(0);
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['uid']) || empty($this->param['bid']) || empty($this->param['type'])){
            //参数有误
            $this->format(2);
            return false;
        }
        if($this->param['type'] == 2){
            if(! is_numeric($this->param['b_c_id']) || $this->param['b_c_id'] < 0){
                //参数有误
                $this->format(2);
                return false;
            }
        }
        return true;
    }
    
}
