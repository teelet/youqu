<?php

/**
 *  request :  www.domain.com/test_test/test
 */

class Test_TestController extends Yaf_Controller_Abstract {

    public function testAction(){
        echo 'I am a test!';exit;
    }
}