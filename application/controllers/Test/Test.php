<?php

/**
 *  request :  www.domain.com/test_test/test
 */

class Test_TestController extends AbstractController {

    public function testAction(){
        echo 'I am a test!';exit;
        
        $this->assign($data);
        return $this->end();
    }
}