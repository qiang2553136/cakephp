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
public function logs($msg) {
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
    //结果转化成数组
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
    if(!$context){
        $this->returnError('网络错误！');
    }
    $result = file_get_contents($url, false, $context);
    return $result;
}
/**
发送邮件
*/
public function send_email($name)   {
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
private function checkSign($arr,$key) {
    $sign=$arr['sign'];
    if(!$sign||strlen($sign)!=32)
    	return false;
    unset($arr['sign']);
    ksort($arr);//排序
    $str='';
    foreach ($arr as $idx=>$value) {
    	$str.=$idx.'='.$value.'&';
    }
    $str.='key='.$key;
    $rightSign=md5($str);
    // $this->log($str.'=>'.$rightSign);
    return $rightSign==$sign;
}
/**
  获取app信息
*/
public function checkAppInfo($params) {

    if(!key_exists('client', $params)) {
      $this->returnError('未验证的客户端');
    }
    if(!key_exists('sign', $params)) {
      $this->returnError('未验证的安全签名');
    }
    $app=SharedMem::getAppInfoById($params['client']);
    if(!$app) {
      $this->returnError('未知的客户端');
    }
    if(!$this->checkSign($params, $app['key'])) {
      $this->returnError('安全签名未通过');
    }
    return $app;
}
/**
    检查需要的参数是否存在
*/
public function checkParams($params) {
    $datas = $this->request->data;
    if (count($datas) == 0) {
        $datas = $this->request->query;
    }
    foreach ($params as $key => $value) {
        if (!array_key_exists($value, $datas) || count($datas[$value]) == 0) {
            echo json_encode(array('success' => 0,'message' => '缺少必要参数:'.$value));
            exit();
        }
    }
    return $datas;
}
/**
    返回失败
*/
public function returnError($message) {
    echo json_encode(array('success' => 0,'message' => $message));
    $this->logs($this->request->here.$message);
    exit();
}
/**
    返回成功
*/
public function returnSucc($message, $data) {
    echo json_encode(array('success' => 1,'message' => $message, 'data' => $data));
    $this->logs($this->request->here.$message);
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
