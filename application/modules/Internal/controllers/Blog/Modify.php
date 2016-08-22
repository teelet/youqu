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
                $pic = Comm_Context::form('img_mod_name', '');
                $pic_name_prefix = explode('_', $pic)[0];
                $pic_ext = end(explode('.', $pic));
                if(! $pic_name_prefix || ! $pic_ext){
                    //参数有误
                    $this->format(2);
                    $this->jsonResult($this->data);
                    return $this->end();
                }

                $img_url = array(
                    'img_name_0' => $pic_name_prefix.'_0.'.$pic_ext, //缩略图
                    'img_name_1' => $pic_name_prefix.'_1.'.$pic_ext, //正文图
                    'img_name_2' => $pic //原图
                );
                
                //更新帖子图片信息
                $res = Blog_BlogModel::updateBlogImage($this->param['g_g_id'], $this->param['uid'],$this->param['bid'], $this->param['imgId_mod'], $img_url, $this->param['atime'], $this->param['is_own']);
            }elseif($this->param['type'] == 5){
                $this->param['img_num'] = Comm_Context::form('img_num', 0); //新上传图片属性
                
                if($this->param['img_num'] >= 1){
                    $img_urls = array();
                    for($i = 1; $i <= $this->param['img_num']; $i++){
                        $key = 'img_name_'.$i;
                        $pic = Comm_Context::form($key, '');
                        $pic_name_prefix = explode('_', $pic)[0];
                        $pic_ext = end(explode('.', $pic));
                        if(! $pic_name_prefix || ! $pic_ext){
                            //参数有误
                            $this->format(2);
                            $this->jsonResult($this->data);
                            return $this->end();
                        }
                        //入库图片名
                        $img_urls[] = array(
                            'pic_name_0' => $pic_name_prefix.'_0.'.$pic_ext, //缩略图
                            'pic_name_1' => $pic_name_prefix.'_1.'.$pic_ext, //正文图
                            'pic_name_2' => $pic //原图
                        );
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
                            'url_0'   => $url['pic_name_0'],
                            'url_1'   => $url['pic_name_1'],
                            'url_2'   => $url['pic_name_2'],
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
