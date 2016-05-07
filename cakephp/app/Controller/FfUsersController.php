<?php
/*
* To change this template, choose Tools | Templates
* and open the template in the editor.
*/

App::uses('AppController', 'Controller');
class FfUsersController extends AppController{

    public $uses = array('Ff_user','Ff_purchase','Ff_product',
    'Ff_effective','Ff_price','Ff_regist','Ff_score','Ff_present',
    'Ff_software','Ff_msgcheck','Ff_loginrecord');

//登陆
    public function Login() {

      $params = $this->request->query;
      $message = '';

      $imei = $params['imei'];
      $software_type = $params['soft'];
      $type = $params['type'];
      $present_time = time();


      $user = $this->Ff_user->find('first',array(
  				'conditions' => array(
  								'Phone_number' => $params['username'],
                  'Password' => md5($params['password'])
                )
            )
           );
      //username也可以当作用户名登陆
      if(!$user){
        $user = $this->Ff_user->find('first',array(
          'conditions' => array(
                  'Username' => $params['username'],
                  'Password' => md5($params['password'])
                 )
             )
            );
      }

      if ($user) {
        $message = '登录成功！';
        //不返回密码
        unset($user['Ff_user']['Password']);
        $result = array('success' => true,'message' =>$message ,'data'=>$user['Ff_user']);
       //保存登陆记录
       $user_id = $user['Ff_user']['Id'];
       $this->Ff_loginrecord->save(array(
          'user_id'=>$user_id,'software_type'=>$software_type,'type'=>$type,
          'imei'=>$imei,'present_time'=>$present_time
        ));
      } else {
        $message = '用户名或密码错误！';
        $result = array('success' => false,'message' => $message);
      }

    $this->log($message);
    echo json_encode($result);
    exit();

}


//产品
  public function Products (){

    $params = $this->request->data;
    $message = '';

    if($params['software_type']){

          $soft = $this->Ff_software->find('first',array(
              'conditions' =>array(
                'software_type'=>$params['software_type']
              )
            ));

            $updatetime = 0;
            if(array_key_exists('updatetime',$params)){
                $updatetime = $params['updatetime'];
            }

            $type_value = $soft['Ff_software']['software_type_value'];

            $productArray = array();

            $user = $this->Ff_product->find('all',array(
              'conditions' => array(
                      'software_type_value' => $type_value,
                      'update_time > ' => $updatetime
                    )
                )
               );


        foreach ($user as $key => $value) {

				$products = $value['Ff_product'];

				$priceArray = array();

        $res = $this->Ff_price->find('all',array(
          'conditions' => array(
                  'product_id' => $value['Ff_product']['Id']

                )
            )
           );

          $priceArray = array();

          foreach ($res as $k => $v) {
            $r = $this->Ff_effective->find('all',array(
              'conditions' => array(
                      'Id' => $v['Ff_price']['effective_id']
                    )
                )
               );
                $te = ($v['Ff_price']);
                $te['price_id']=$te['Id'];
                foreach($r as $i => $n){
                     $n['Ff_effective'];
                     $price_effective=array_merge($te,$n['Ff_effective']);
                }
              array_push($priceArray,$price_effective);
          }
				$products['price'] = $priceArray;
        $products['product_id'] = $products['Id'];
        // echo json_encode($products);
				array_push($productArray,$products);
			}

      $message = '返回科目成功！';
      echo json_encode($productArray);

    }
    else{

      $message = '非法参数';
      echo json_encode(array('message' => $message));

    }

      $this->log($message);
      exit();

  }

//充值
public function Purchases(){
      $params = $this->request->data;
      $message = '';

      $phoneNumber = $params['phone_number'];
  		$purchases_time = time();
  		$store_price = $params['store_price'];
  		$transaction_Identifier = $params['transaction_Identifier'];
  		$local = $params['local'];

  	   $data = array('user_id'=>$uid,'purchases_time'=>$purchases_time,
                  'store_price'=>$store_price,'transaction_Identifier'=>$transaction_Identifier,
                  'local'=>$local);

  		if ($this->Ff_purchase->save($data)) {

          $res = $this->Ff_user->find('first',array(
            'conditions' => array(
              'Phone_number' => $phoneNumber
                  )
          ));

        $r = $this->Ff_user->save(array('id'=>$res['Ff_user']['Id'],'Balance'=>$res['Ff_user']['Balance']+$store_price));

  			if ($r) {
          $message = '充值成功！';
  				echo json_encode (array('success' => true,'message' => $message ));

  			} else {
          $message = '充值失败！';
  				echo json_encode (array('success' => false,'message' => $message));

  			}
        $this->log($message);
        exit();

  		} else {
        $message = '充值失败！(系统错误)';
  			echo json_encode (array('success' => false,'message' => $message));


  		}

      $this->log($message);
      exit();


}

//GetProductPriceById
public function Price(){

  $params = $this->request->data;
  $message = '';

  $productArray = array();

  if ($params['product_id']) {
  			$res = $this->Ff_price->query("SELECT * FROM `ff_prices` AS `p` LEFT JOIN `ff_effectives` AS `e` ON `p`.effective_id=`e`.id WHERE  `p`.product_id = ".$params['product_id']);

        foreach ($res as $key => $value) {
          $temp = array_merge($value['p'],$value['e']);
          $temp['price_id'] = $value['p']['Id'];
          array_push($productArray,$temp);
        }

        $message = '获取价格成功！';
  			echo json_encode(array('success' => true,'message' => $message ,'data' => $productArray));

  		} else {

        $message = '非法参数！';
  			echo json_encode(array('success' => false,'message' => $message));

  		}

      $this->log($message);
      exit();

}
//UserProducts
public function UserProducts(){
  $params = $this->request->data;
  $message = '';

  $productArray=array();

  // echo json_encode($params['uid']);
  if ($params['phone_number']) {

    $phoneNumber = $params['phone_number'];
    $res = $this->Ff_user->find('first',array(
      'conditions' => array(
        'Phone_number' => $phoneNumber
            )
    ));

  			$res = $this->Ff_regist->query("SELECT * FROM `ff_regists` `r` LEFT JOIN `ff_products` `p` ON `r`.product_id = `p`.Id WHERE `r`.user_id = ".$res['Ff_user']['Id']);

        foreach ($res as $key => $value) {
          $temp = array_merge($value['p'],$value['r']);
          array_push($productArray,$temp);
        }

        $message = '查询成功！';
  			echo json_encode(array('success' => true,'message' => $message ,'data' => $productArray));

  		} else {
        $message = '非法参数';
  			echo json_encode(array('success' => false,'message' => $message));

  		}
      $this->log($message);
      exit();
}

//RegistProduct
public function RegistProduct(){

  $params = $this->request->data;
  $message = '';

  $productArray = array();
  $effective_time = time();
  $phoneNumber = $params['phone_number'];

  $res = $this->Ff_user->find('first',array(
    'conditions' => array(
      'Phone_number' => $phoneNumber
          )
  ));

  $uid = $res['Ff_user']['Id'];


  $res = $this->Ff_regist->query("SELECT * FROM `ff_regists` `r` LEFT JOIN `ff_effectives` `e` ON `e`.id = `r`.effective_type WHERE `r`.user_id=".
  $uid." AND `r`.product_id=".$params['product_id']." ORDER BY `r`.valid_time DESC");

  foreach ($res as $key => $value) {
    $temp = array_merge($value['e'],$value['r']);
    array_push($productArray,$temp);
  }

  		if ($productArray) {
  				$lastRecord = $productArray[0];
  				$effective_time = $lastRecord['valid_time'] + $lastRecord['days']*60*60*24;
  		}

  $effRes = $this->Ff_effective->findById($params['effective_type']);
  $days = $effRes['Ff_effective']['days'];
  $expire_time = $effective_time + $days*24*60*60;
  $effective_type = $params['effective_type'];
  $data = array('user_id'=>$uid,'product_id'=>$params['product_id'],
                        'purchases_time'=>time(),'valid_time'=>$effective_time,
                      'expire_time'=>$expire_time,'effective_type'=>$effective_type);

   $user = $this->Ff_user->findById($uid);

    if($params['price']<0){
      $message = '价格有误请联系管理员！';
      echo json_encode(array('success' => false,'message' => $message));
      $this->log($message);
      exit();
    }

  		if ($this->Ff_regist->save($data)) {

        $rid = $this->Ff_regist->id;
        $info = array('expire_time'=>$expire_time,'valid_time'=>$effective_time);

          //如果积分足够先扣积分
          if($user['Ff_user']['Score']-$params['price']>=0){

            $user['Ff_user']['Score']=$user['Ff_user']['Score']-$params['price'];

            if ($this->Ff_user->save(array('id'=>$uid,'Score'=>$user['Ff_user']['Score']))) {
                $r = $this->Ff_regist->findById($rid);

              $message = '购买成功！';
              echo json_encode(array('success' => true,'message' => $message,'data'=>$r['Ff_regist']));

            }else{
              $message = '购买失败！';
              echo json_encode(array('success' => false,'message' => $message));

            }

            $this->log($message);
            exit();

          }else{

            $user['Ff_user']['Score']=$user['Ff_user']['Score']-$params['price'];
            $user['Ff_user']['Balance']=$user['Ff_user']['Balance']+$user['Ff_user']['Score'];

            if($user['Ff_user']['Balance']>=0){

              if ($this->Ff_user->save(array('id'=>$uid,'Balance'=>$user['Ff_user']['Balance'],'Score'=>0))) {

                $r = $this->Ff_regist->findById($rid);
                $message = '购买成功！';
                echo json_encode(array('success' => true,'message' => $message,'data'=>$r['Ff_regist']));

              }else{

                $message = '购买失败！';
                echo json_encode(array('success' => false,'message' => $message));

              }

              $this->log($message);
              exit();

            }else{

              $message = '购买失败！';
              echo json_encode(array('success' => false,'message' => '购买失败！'));

            }
            $this->log($message);
            exit();
          }
      }else{
        $message = '购买失败！（系统错误）';
        echo json_encode(array('success' => false,'message' => $message));

      }

      $this->log($message);
      exit();

}

//赠送积分

public function PresentScore (){

     $params = $this->request->data;
     $message = '';

     $time = time();
     $s=$this->Ff_software->find('first',array(
       'conditions' => array(
         'software_type' => $params['software_type']
       )
     ));

     $sql = 'SELECT `Ff_score`.`id`, `Ff_score`.`days`, `Ff_score`.`score`, `Ff_score`.`software_type_value` FROM `fenfen`.`ff_scores` AS `Ff_score` WHERE software_type_value & '.$s['Ff_software']['software_type_value'].' != 0';
     $rule = $this->Ff_score->query($sql);

     if($rule[0]){
       $rule = $rule[0];
     }else{
       $message = '软件类型不存在！';
       $this->log($message);
       exit();
     }

     $ruletime = $rule['Ff_score']['days'];

     $phone_number = $params['phone_number'];

     $user = $this->Ff_user->find('first',array(
              'conditions' => array(
              'Phone_number' => $params['phone_number']
            )
        ));

     $user = $user['Ff_user'];

     $present=$this->Ff_present->find('first',array(
       'conditions' => array(
         'user_id' => $user['Id']
       ),
       'order' => array('Ff_present.present_time' => 'desc')
         ));

        if($present){

         if($time-$present['Ff_present']['present_time']>($ruletime*60*60*24)){

           $this->Ff_present->save(array('user_id'=>$user['Id'],'present_time'=>$time,'score'=>$rule['Ff_score']['score']));

           $user['Score'] = $user['Score']+$rule['Ff_score']['score'];

           $this->Ff_user->save(array('id'=>$user['Id'],'Score'=>$user['Score']));

           $message = '积分领取成功！';
           echo json_encode(array('success' => true,'message' => $message,'data'=>$user));

         }else{

           $message = '您已经获得该积分,不能重复领取！';
           echo json_encode(array('success' => false,'message' => $message));

         }

        $this->log($message);
        exit();

       }else{
         $this->Ff_present->save(array('user_id'=>$user['Id'],'present_time'=>$time,'score'=>$rule['Ff_score']['score']));

         $user = $this->Ff_user->findById($user['Id']);

         $user = $user['Ff_user'];

         $user['Score'] = $user['Score']+$rule['Ff_score']['score'];

         $this->Ff_user->save(array('id'=>$user['Id'],'Score'=>$user['Score']));

         $message = '积分领取成功！';
         echo json_encode(array('success' => true,'message' => $message,'data'=>$user));

       }

       $this->log($message);
       exit();

}
//注册用户，重置密码验证
public function RegistCheck (){

  $params = $this->request->data;
  $message = '';

  $phone_number = $params['phone_number'];
  $password = $params['password'];
  $checkcode = $params['checkcode'];
  //过期时间
  $overduedays = 1*60*60;

  $check = $this->Ff_msgcheck->find('first',array(
    'conditions' => array(
      'phone_number' => $phone_number,
      'checkcode'=>$checkcode,
      'status' =>1
    ),
    'order' => array('Ff_msgcheck.present_time' => 'desc')
  ));


  if(array_key_exists('Ff_msgcheck',$check)){
    $check = $check['Ff_msgcheck'];
      if($check['flag']==0){
        $message = '注册成功！';
      }else{
        $message = '重置密码成功！';
      }

      if(time()-$check['present_time']>$overduedays){
        $message = '验证码失效，请重新申请！';
        echo json_encode(array('success' => false,'message' => $message));
        $this->log($message);
        exit();
      }


  }

   if($check){

     $user=$this->Ff_user->find('first',array(
       'conditions' => array(
         'Phone_number' => $params['phone_number']
             )
         ));

     if ($user) {
      $message = '此手机号已存在！';
      $result = array('success' => false,'message' => $message);
     } else {

     $this->Ff_user->save(array('Username'=>'','Phone_number'=>$phone_number,'Password'=>md5($password),
     'Balance'=>0,'Score'=>0,'Status'=>1));

     $res = $this->Ff_user->findById($this->Ff_user->id);

     if ($res) {
     $result = array('success' => true,'message' => $message,'data'=>$res['Ff_user']);
     $this->Ff_msgcheck->save(array('id'=>$check['Id'],'status'=>2));
     } else {
     $message = '注册失败！(系统错误)';
     $result = array('success' => false,'message' => $message);
     }
    }
     $this->log($message);
     echo json_encode($result);
     exit();

   }else{
     $this->log($message);
     echo json_encode(array('success' => false,'message' => '验证码有误！'));
     exit();
   }



}

//注册
    public function Regist(){

      $params = $this->request->data;
      $message = '';

      $phoneNumber = $params['phone_number'];
      $flag = $params['flag'];
      $status = 0;
      //生成4位随机数
      $checkcode = rand(1000,9999);
      $present_time = time();

      $user=$this->Ff_user->find('first',array(
        'conditions' => array(
          'Phone_number' => $phoneNumber
            )
          ));



      if ($user) {
       $message = '此手机号已经注册！';
       $result = array('success' => false,'message' => $message);
      } else {
       $this->Ff_msgcheck->save(array('phone_number'=>$phoneNumber,'checkcode'=>$checkcode,
                                  'present_time'=>$present_time,'status'=>$status,'flag'=>$flag));

       $message = '申请成功！';
       $result = array('success' => true,'message' => $message);

   }

  $this->log($message);
  echo json_encode($result);
  exit();

}

  //  ModInfo 修改用户名密码
  public function ModInfo() {

    $params = $this->request->data;
    $message = '';

    $oldpwd = '';
    $newpwd = '';
    $name = '';
    $phoneNumber = $params['phone_number'];

  if(array_key_exists('oldpwd',$params)){
      $oldpwd = $params['oldpwd'];
  }

  if(array_key_exists('newpwd',$params)){
      $newpwd = $params['newpwd'];
  }

  if(array_key_exists('name',$params)){
      $name = $params['name'];
  }

  $res = '';

    if($name){

        $user=$this->Ff_user->find('first',array(
            'conditions' => array(
              'Phone_number' => $phoneNumber
                  )
          ));

      			if ($user) {
      				$data=array('id'=>$user['Ff_user']['Id'],'Username'=>$name);
              $this->Ff_user->save($data);
              $res = $this->Ff_user->findById($this->Ff_user->id);

      				if ($res) {
                $message = '修改成功！';
      					$result = array('success' => true,'message' => $message,'data' => $res['Ff_user']);
      				} else {
                $message = '修改失败！';
      					$result = array('success' => false,'message' => $message);
      				}
      			} else {
              $message = '该用户不存在！';
              $result = array('success' => false,'message' => $message);
            }

    }

    if($newpwd!=null && $oldpwd!=null){

      $user = $this->Ff_user->find('first',array(
        'conditions' => array(
          'Phone_number' => $phoneNumber,
          'Password' => MD5($oldpwd)
              )
          ));

          if ($user) {
            $data=array('id'=>$user['Ff_user']['Id'],'Password'=>md5($newpwd));
            $this->Ff_user->save($data);
            $res = $this->Ff_user->findById($this->Ff_user->id);
            if ($res){
              $message = '修改成功！';
              $result = array('success' => true,'message' => $message,'data' => $res['Ff_user']);
            } else {
              $message = '密码修改失败！';
              $result=array('success' => false,'message' => $message);
            }
          } else {
            $message = '传入信息有误！';
            $result = array('success' => false,'message' => $message);
          }

    }
        $this->log($message);
        echo json_encode($result);
        exit();

  }
  //发送短信
public function SendMsg(){

    $check = $this->Ff_msgcheck->find('all',array(
      'conditions' => array(
        'status' => 0
      ),
      'order' => array('Ff_msgcheck.present_time' => 'desc')
    ));

  $a=array();

  foreach ($check as $key => $value) {
    array_push($a,$value['Ff_msgcheck']);
  }

    echo json_encode(array('success' => true,'message' => '查询成功！','data' => $a));
    $this->log('查询成功！');
    exit();

  }
  //发送成功更改数据库状态
  public function MsgStatus(){

    $params = $this->request->data;

    $msgid = $params['msgid'];

    $this->Ff_msgcheck->save(array('id'=>$msgid,'status'=>1));

    echo json_encode(array('success' => true,'message' => '修改成功！'));
    $this->log('修改成功！');
    exit();

  }



}
?>
