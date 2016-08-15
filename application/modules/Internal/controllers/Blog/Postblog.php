<?php
/**
 * 用户发帖
 * shaohua
 */

class Blog_PostblogController extends AbstractController {
    
    public function indexAction() {
        //获取参数
        $this->param['uid']     = (int) Comm_Context::form('uid', 0);
        $this->param['g_g_id']  = (int) Comm_Context::form('g_g_id', 0); //游戏社区g_g_id
        $this->param['title']   = Comm_Context::form('title', '');
        $this->param['content'] = Comm_Context::form('content', '');
        $this->param['address'] = Comm_Context::form('address', '');
        $this->param['pic_num'] = (int) Comm_Context::form('pic_num', 0);
        $this->param['atime']   = Comm_Context::form('atime', date('Y-m-d H:i:s'));
        $this->param['ctime']   = (int) Comm_Context::form('ctime', time());
        //参数检查
        if($this->checkParam()){
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
            
            //blog信息入库
            //从发号器中获取预先给定的bid，便于后期异步入库
            $bid = IndexmakerModel::makeIndex(2);
            if(! $bid){//网络异常
                $this->format(3);
                $this->jsonResult($this->data);
                return $this->end();
            }
            $this->param['bid'] = $bid;
            Blog_BlogModel::insertBlog($this->param);
            //图片入库
            if(count($pic_urls) > 0){
                $b_i_id_end = IndexmakerModel::makeIndex(3, $this->param['pic_num']);
                if(! $b_i_id_end){//网络异常
                    $this->format(3);
                    $this->jsonResult($this->data);
                    return $this->end();
                }
                $b_i_id_start = $b_i_id_end - $this->param['pic_num'] + 1;
                $urls = array();
                foreach ($pic_urls as $url){
                    $urls[$b_i_id_start] = $url;
                    $b_i_id_start++;
                }
                $res = Blog_BlogModel::insertBlogImage($bid, $urls);
                if(! $res){//网络异常
                    $this->format(3);
                    $this->jsonResult($this->data);
                    return $this->end();
                }
                //拼接images的redis缓存
                $image_info = array();
                foreach ($urls as $b_i_id => $url){
                    $image_info[$b_i_id] = array(
                        'b_i_id'  => $b_i_id,
                        'bid'     => $bid,
                        'url_0'   => '',
                        'url_1'   => '',
                        'url_2'   => $url,
                        'atime'   => $this->param['atime'],
                        'ctime'   => $this->param['ctime'],
                        'summary' => ''
                    );
                }
                Blog_BlogModel::setBlogImage($image_info);
            }
            
            //初始化帖子点赞，回帖数等
            Blog_BlogModel::initBlogActionCount($bid);
            //拼接blog的redis缓存
            Blog_BlogModel::setBlogDetail($this->param); 
            //将bid入对应社区的redis集合中
            Blog_BlogModel::addToGroupBlog($this->param['g_g_id'], $bid);
            //将bid入对于社区用户的redis集合中
            Blog_BlogModel::addToUserBlog($this->param['g_g_id'], $this->param['uid'], $bid);
            
            //操作成功
            $this->format(0);
        } 
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        if(empty($this->param['uid']) || empty($this->param['g_g_id']) || empty($this->param['title']) || empty($this->param['content'])){
            //参数有误
            $this->format(2);
            return false;
        }
        return true;
    }
    
}

