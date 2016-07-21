<?php
/**
 * Index 模块下，所有controller的父类
 */

class AbstractController extends Yaf_Controller_Abstract {
    
    /*模版文件*/
    protected $tpl = '';
    
    /*模版渲染*/
    public function assign($data, $return_string = false){
        try {
            $view = new Yaf_View_Simple(TPL_PATH);
            $view->assign($data);
            $html = $view->render($this->tpl);
            if($return_string){
                return $html;
            }else{
                echo $html;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}