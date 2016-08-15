<?php
/**
 * 获取顶导
 * shaohua
 */

class Home_GetnavController extends AbstractController {

    public function indexAction() {
        //参数检查
        if($this->checkParam()){
            $res = Nav_NavModel::getNav();
            if($res){
                $this->format(0);
                $this->data['results'] = $res;
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
