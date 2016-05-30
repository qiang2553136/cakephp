<?php
/**
 * Application level Controller
 *
 * This file is application-wide controller file. You can put all
 * application-wide controller-related methods here.
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       app.Controller
 * @since         CakePHP(tm) v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

App::uses('Controller', 'Controller');
App::uses('SharedMem', 'Lib');


/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @package		app.Controller
 * @link		http://book.cakephp.org/2.0/en/controllers.html#the-app-controller
 */
class AppController extends Controller {

/**
输出日志
*/
public function log($msg)
{
  $logName='log'.date('Ymd',time());
  CakeLog::config($logName, array(
      'engine' => 'File',
      'path' => LOGS.DS
  ));
  parent::log($msg,$logName);
}
/**
分页
*/
public function paging($model,$limit){

  $count =  $this->$model->find('count');
  $pagecount = $count/$limit;

  $re = $this->$model->find('all');
  /**
  结果转化成数组
  */
  $k = array();
  foreach ($re as $key => $value) {
        $temp = array_merge($value[$model]);
        array_push($k,$temp);
      }

  $result=array();
  $pages = 0;
  for ($i=0; $i < $pagecount; $i++) {
  $output = array_slice($k, $limit*$i,$limit);
  $pages = $i+1;
  $result[$pages] = $output;
  }
  //记录总数
  $result['count'] = $count;
  //分页总数
  $result['pages'] = $pages;

  return $result;


}
/**
  向指定url提交post数据
*/
public function send_post($url, $post_data) {
  //传入的数组转换为JSON格式
  $postdata = json_encode($post_data);
  $options = array(
    'http' => array(
      'method' => 'POST',
      'header' => 'Content-type:application/x-www-form-urlencoded',
      'content' => $postdata,
      'timeout' => 15 * 60 // 超时时间（单位:s）
    )
  );
  $context = stream_context_create($options);
  $result = file_get_contents($url, false, $context);

  return $result;
}
/**
发送邮件
*/
public function send_email($name)
    {
        App::uses('CakeEmail','Network/Email');
        $Email = new CakeEmail('gmail');
        $Email->from(array('guozhiqiang@appfenfen.com' => '系统'))
            ->to('403131588@qq.com')
            ->subject('帐号异常')
            ->send('帐号：'.$name.'余额出现异常，请及时处理！');
    }
/**
  验签
*/
private function checkSign($arr,$key)
{

    		$sign=$arr['sign'];
    		if(!$sign||strlen($sign)!=32)
    			return false;
    		unset($arr['sign']);
    		ksort($arr);//排序
    		//$arr['key']=$key;
    		$str='';
    		foreach ($arr as $idx=>$value)
    		{
    			$str.=$idx.'='.$value.'&';
    		}
    		$str.='key='.$key;
    		$rightSign=md5($str);
    		$this->log($str.'=>'.$rightSign);
    		return $rightSign==$sign;
}
/**
  获取app信息
*/
public function getAppInfo()
	{
		$params=$this->request->data;
		if(!key_exists('client', $params)||!key_exists('sign', $params))
		{
			$this->log('缺少关键参数[client或sign]');
			return false;
		}
		$app=SharedMem::getAppInfoById($params['client']);
		if(!$app)
		{
			$this->log('当前应用client不存在');
			return false;
		}
		if(!$this->checkSign($params, $app['key']))
		{
			$this->log('验证签名失败');
			return false;
		}

		$this->log('验证签名成功');
		return $app;
	}
/**
  检查需要的参数是否存在
*/
public function checkParams($pa){
    $params=$this->request->data;
    $res = '';
    foreach ($pa as $key => $value) {
      if(!array_key_exists($value, $params)){
        $res = $res.'缺少'.$value.'</br>';
        $this->log($this->request->here.'缺少'.$value);
      }
    }
    if($res!=''){
      echo $res;
      exit();
    }

}
/**
  输出json
*/
public function stopProgram($result){

    //生成日志
    $this->log($this->request->here.$result['message']);
    //输出json
    echo json_encode($result);
    //停止向下执行
    exit();

}



// public function format($model){
//   $k = array();
//   foreach ($model as $key => $value) {
//         $temp = array_merge($value[$model]);
//         array_push($k,$temp);
//       }
//       return $k;
// }


}
