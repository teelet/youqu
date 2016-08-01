<?php

//request: domain/internal/index/index 或 i.domain/index/index

class IndexController extends AbstractController {
    
    public function indexAction(){
        $data = array();
        $model = new SampleModel();
        $data['name1'] = $model->selectSample();
        $data['name2'] = Comm_Context::param('name', 'asd');
        
        $this->jsonResult($data);
        return $this->end();  
    }
    
}