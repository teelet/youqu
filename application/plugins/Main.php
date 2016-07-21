<?php
/**
 * Plugin yaf自动6个hook，first load first call
 */

class MainPlugin extends Yaf_Plugin_Abstract{
    
    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
        //设置路由分发
        $host = $request->getServer('HTTP_HOST');
        $host_pre = substr($host, 0, strpos($host, '.'));
        if($host_pre == 'i'){
            $request->setModuleName('Internal');
        }else{
            $request->setModuleName('Index');
        }
	}

	public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
	}

	public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
	}

	public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
	}

	public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
	}

	public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {
	}
}
