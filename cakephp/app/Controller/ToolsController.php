<?php

App::uses('Controller', 'Controller');

class ToolsController extends AppController {

    public $uses = array('Ff_msgcheck');
//发送短信
public function SendMsg(){

    $check = $this->Ff_msgcheck->find('all',array(
        'conditions' => array(
            'status' => 0
        ),
        'order' => array('Ff_msgcheck.present_time' => 'desc')
    ));
    $data=array();
    if(count($check)==0){
        $this->returnSucc('没有查到数据！',$data);
    }
    foreach ($check as $key => $value) {
        array_push($data,$value['Ff_msgcheck']);
    }

    $this->returnSucc('查询成功！',$data);

}
//发送成功更改数据库状态
public function MsgStatus(){

    $params =$this->checkParams(array("msgid"));

    $this->Ff_msgcheck->save(array('id'=>$params['msgid'],'status'=>1));

    $this->returnSucc('修改成功！','');
}


}
