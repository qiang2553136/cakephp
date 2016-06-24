<?php

App::uses('Controller', 'Controller');

class ToolsController extends AppController {

    public $uses = array('Ff_msgcheck','Ff_score','Ff_software');
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
public function getScoreInfo(){

    $params =$this->checkParams(array("soft"));
    $this->checkAppInfo($params);
    // $params=$this->request->query;

    $soft = $this->Ff_software->find('first',array(
              'conditions'    =>array(
                  'software_type' =>$params['soft']
                  )
            ));
    if(!$soft){
        $this->returnError('软件类型不存在！');
    }


    $sql = 'SELECT *
    FROM `ff_scores` AS `Ff_score`
    WHERE software_type_value & '.$soft['Ff_software']['software_type_value'].' != 0';

    $scoreData = $this->Ff_score->query($sql);
    if(count($scoreData)==0){
        $this->returnError('没有相关数据！');
    }

    $this->returnSucc('查询成功！',$scoreData);

}


public function test() {
    // $this->layout = 'ajax';//布局样式
    $this->layout = 'ajax';

    // $this->createFolder("../../ee");
}
public function flashapp(){
    $result=ClassRegistry::init(array(
            'class' => 'ff_apps', 'alias' => 'app'));
    $datasource=$result->find('all');
    Cache::write('apps', $datasource, 'long');

    exit();
}

public function upload(){
    if(!$_FILES){
        $this->returnError('未发现文件');
    }
  if (($_FILES["file"]["size"]/1024/1024 < 2)) {
    if ($_FILES["file"]["error"] > 0) {
        echo "Return Code: " . $_FILES["file"]["error"] . "<br />";
    }
    else
    {
        echo "Upload: " . $_FILES["file"]["name"] . "<br />";
        echo "Type: " . $_FILES["file"]["type"] . "<br />";
        echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
        echo "Temp file: " . $_FILES["file"]["tmp_name"] . "<br />";

    if (file_exists("../../../" . $_FILES["file"]["name"]))
      {
      echo $_FILES["file"]["name"] . " already exists. ";
      }
    else
      {
      move_uploaded_file($_FILES["file"]["tmp_name"],
      "../../../" . $_FILES["file"]["name"]);
      $this->redirect($this->referer());
      echo "Stored in: " . "" . $_FILES["file"]["name"];
      }
    }
  }
else
  {
  echo "Invalid file";
  }

    exit();
}
function createFolder($path)
{
  mkdir($path, 0777);

}

}
