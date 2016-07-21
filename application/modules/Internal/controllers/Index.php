<?php

//request: domain/internal/index/index 或 i.domain/index/index

class IndexController extends Yaf_Controller_Abstract {
    
    public function indexAction(){
        $model = new SampleModel();
        echo $model->selectSample();
    }
    
}