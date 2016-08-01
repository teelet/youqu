<?php
/**
 * 社区首页推荐社区列表
 * shaohua
 */

class Group_RecommendgroupController extends AbstractController {
    
    const COUNT = 10;
    
    public function indexAction() {
        //获取参数
        $this->param['uid'] = (int) Comm_Context::param('uid', 0);
        //参数检查
        $all = $list = $rec = array();
        if($this->checkParam()){
            if($this->param['uid']){ //取最新加入的社区
                $all = $list = Group_GamegroupModel::getUserNewGroup($this->param['uid']);
                if(count($list) < self::COUNT){ //取用户加入的游戏下的社区
                    //获取用户游戏
                    
                }
            }
            //取推荐
            if(count($list) < self::COUNT){
                $rec = Group_GamegroupModel::getRecommendGroup();
                if($rec){
                    $all = array_merge($list, $rec);
                }
            }
            //获取group的详细信息
            $group_info = array(); 
            if($all){
                foreach (array_unique($all) as $one){
                    $group_info[$one] = Group_GamegroupModel::getGroupInfo($one);
                }
            }
            //格式化数据
            $res = array();
            if(!empty($list)){
                foreach ($list as $v){
                    if(isset($group_info[$v])){
                        $group_info[$v]['is_recommend'] = false;
                        $res[] = $group_info[$v];
                    }
                }
            }
            if(!empty($rec)){
                foreach ($rec as $v){
                    if(in_array($v, $list)){
                        continue;
                    }
                    if(isset($group_info[$v])){
                        $group_info[$v]['is_recommend'] = true;
                        $res[] = $group_info[$v];
                    }
                }
            }
            $res = array_slice($res, 0, self::COUNT);
            
            if($res){
                $this->data['results'] = $res;
                $this->format(0);
            }else{
                $this->format(3);
            }
            
        }
        $this->jsonResult($this->data);
        return $this->end();
    }
    
    public function checkParam(){
        return true;
    }
    
}
