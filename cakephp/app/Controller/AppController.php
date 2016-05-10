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

// public function format($model){
//   $k = array();
//   foreach ($model as $key => $value) {
//         $temp = array_merge($value[$model]);
//         array_push($k,$temp);
//       }
//       return $k;
// }


}
