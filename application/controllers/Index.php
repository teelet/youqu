<?php
/**
 * @name IndexController
 * @author root
 * @desc 默认控制器
 * @see http://www.php.net/manual/en/class.yaf-controller-abstract.php
 */
class IndexController extends AbstractController {

	/** 
     * 默认动作
     * Yaf支持直接把Yaf_Request_Abstract::getParam()得到的同名参数作为Action的形参
     */
    protected $tpl = 'index/index.phtml';
    
	public function indexAction($name = "Stranger") {
	    $data = array();
		$get = $this->getRequest()->getQuery("name", "aaa");
		$data['name'] = $get;
		$this->assign($data);
		return $this->end();
	}
}
