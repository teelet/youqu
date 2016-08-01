<?php
/**
 * Index 模块下，所有controller的父类
 */

class AbstractController extends Yaf_Controller_Abstract {
    /*目前限于接口使用*/
    protected $param = array();  //参数
    protected $data = array();   //结果
    
    /*模版文件*/
    protected $tpl = '';
    
    /*模版渲染*/
    public function assign(&$data, $return_string = FALSE){
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
    
    /*输出json串*/
    public function jsonResult(&$data, $json_encode = TRUE) {
        try {
            if($json_encode){
                header('Content-type:text/json; charset=utf-8');
                echo json_encode($data);
            }else{
                return json_decode($data);
            }
        }catch (Exception $e) {
            echo $e->getMessage();
        } 
    }
    
    public function end() {
        //关闭自动渲染
        return FALSE;
    }
    
    /*
     * 格式化结果
     * status 报告信息基本 0 成功， 1 失败， 2 参数有误， 3 网络问题, 4 用户重复操作
     */
    public function format($status = 0){
        switch ($status) {
            case 0 :
                $this->data['statusCode'] = Comm_Config::getPhpConf('error/iErrorMsg.statusCode.success');
                $this->data['message'] = Comm_Config::getPhpConf('error/iErrorMsg.message.successMsg');
                break;
            case 1 :
                $this->data['statusCode'] = Comm_Config::getPhpConf('error/iErrorMsg.statusCode.error');
                $this->data['message'] = Comm_Config::getPhpConf('error/iErrorMsg.message.errorMsg');
                break;
            case 2 :
                $this->data['statusCode'] = Comm_Config::getPhpConf('error/iErrorMsg.statusCode.error');
                $this->data['message'] = Comm_Config::getPhpConf('error/iErrorMsg.message.paramMsg');
                break;
            case 3 :
                $this->data['statusCode'] = Comm_Config::getPhpConf('error/iErrorMsg.statusCode.error');
                $this->data['message'] = Comm_Config::getPhpConf('error/iErrorMsg.message.netMsg');
                break;
            case 4 :
                $this->data['statusCode'] = Comm_Config::getPhpConf('error/iErrorMsg.statusCode.error');
                $this->data['message'] = Comm_Config::getPhpConf('error/iErrorMsg.message.againMsg');
                break;
        }
    }
}