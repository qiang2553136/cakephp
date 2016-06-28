<?php

App::uses('Controller', 'Controller');

class ToolsController extends AppController {

public $uses = array('Ff_msgcheck','Ff_score','Ff_software','Ff_product');

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
// public function test() {
//     // $this->layout = 'ajax';//布局样式
//     $this->layout = 'ajax';
//
//     // $this->createFolder("../../ee");
// }
public function flashapp(){
    $result=ClassRegistry::init(array(
            'class' => 'ff_apps', 'alias' => 'app'));
    $datasource=$result->find('all');
    Cache::write('apps', $datasource, 'long');

    exit();
}
//文件上传
public function upload(){

    $verifyToken = md5('unique_salt' . $_POST['timestamp']);


    if (!empty($_FILES) && $_POST['token'] == $verifyToken) {
        $name = explode('.', $_FILES["Filedata"]["name"], 2);
        $time = $_POST['timestamp'];
    	$tempFile = $_FILES['Filedata']['tmp_name'];

    	// Validate the file type
    	$fileTypes = array('zip','sql','txt'); // 设置什么后缀可以上传
    	$fileParts = pathinfo($_FILES['Filedata']['name']);

    	if (in_array($fileParts['extension'],$fileTypes)) {
            $this->createFolder("../".$name[0]);
            move_uploaded_file($tempFile,
            "../".$name[0]."/".$time.'.zip');

            $this->Ff_product->findById($name[0]);

            $this->Ff_product->id = $name[0];
            $pro = $this->Ff_product->read();
            if(!$pro){
                echo '科目不存在！';
            }
            $this->Ff_product->saveField('update_time',$time); //更新数据



    	} else {
    		echo 'Invalid file type.';
    	}
    }

    exit();
}
function createFolder($path){
    if(!is_dir($path)){
        mkdir($path, 0777);
    }
}
function index(){

    $this->layout = 'ajax';
    // $redis = new redis();
    // $redis->connect('127.0.0.1', 6379);
    // $result = $redis->incr('test');
    // echo($result);   //结果：string(11) "11111111111"
    // exit();

}


}
