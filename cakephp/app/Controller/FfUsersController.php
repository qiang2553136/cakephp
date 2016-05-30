<?php
/*
* To change this template, choose Tools | Templates
* and open the template in the editor.
*/

App::uses('AppController', 'Controller');
class FfUsersController extends AppController{

    public $uses = array('Ff_user','Ff_purchase','Ff_product',
    'Ff_effective','Ff_price','Ff_regist','Ff_score','Ff_present',
    'Ff_software','Ff_msgcheck','Ff_loginrecord','Ff_expiredtime');


public function test(){
  $params = $this->request->query;

  $k = Router::url();


  echo $k;
  exit();
}
//登陆
public function Login() {

      $params = $this->request->data;
      $message = '';
      $this->checkParams(array("username",'password','imei','soft','type'));
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

        /**
        不返回密码
        */
        unset($user['Ff_user']['Password']);

        $result = array('success' => 1,'message' =>$message ,'data'=>$user['Ff_user']
                      );
       //保存登陆记录
       $user_id = $user['Ff_user']['Id'];
       $this->Ff_loginrecord->save(array(
          'user_id'=>$user_id,'software_type'=>$software_type,'type'=>$type,
          'imei'=>$imei,'present_time'=>$present_time
        ));
      } else {
        $message = '用户名或密码错误！';
        $result = array('success' => 0,'message' => $message);
      }

    $this->log($this->request->here.$message);
    echo json_encode($result);
    exit();

}


//验证
public function AppleProving(){

  //60832983e2af11e5a078001c42cf77c3  验签key
  $params = $this->request->data;
  // $receipt = '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';
  $this->checkParams(array("receipt",'uid','balance','quantity','transaction_Identifier','purchases_time'));
  $receipt = $params['receipt'];
  $uid = $params['uid'];
  $message = '';
  //测试服务器
  $res = $this->send_post('https://sandbox.itunes.apple.com/verifyReceipt',array('receipt-data'=>$receipt));
  //正式服务器
  // $res = $this->send_post('https://buy.itunes.apple.com/verifyReceipt',array('receipt-data'=>$receipt));

  $k = json_decode($res,true);

  if(!$k){
    $this->stopProgram(array('success' => 0,'message' => '苹果验证未通过！'));
  }
  // $balance = $k['receipt']['in_app'][0]['product_id'];
  // $quantity = $k['receipt']['in_app'][0]['quantity'];
  // $transaction_Identifier = $k['receipt']['in_app'][0]['transaction_id'];
  // $purchases_time = $k['receipt']['in_app'][0]['purchase_date_ms']/1000;
  $balance = $params['balance'];
  $quantity = $params['quantity'];
  $transaction_Identifier = $params['transaction_Identifier'];
  $purchases_time = $params['purchases_time'];

  //保存充值信息
  $this->Ff_purchase->save(array(
    'user_id' => $uid,
    'purchases_time' => $purchases_time,
    'store_price' => $balance*$quantity,
    'transaction_Identifier' => $transaction_Identifier,
    'receipt' => $receipt
  ));
  //获取新增充值信息的ID
  $purchaseid = $this->Ff_purchase->id;
  //获取苹果验证的状态
  $state = $k['status'];
  //增加用户余额
  $this->Ff_user->id = $uid;
  $post = $this->Ff_user->read();  //读取数据
  if(!$post){
    $this->stopProgram(array('success' => 0,'message' => '用户不存在！'));
  }
  $this->Ff_user->saveField('Balance', $post['Ff_user']['Balance']+$balance*$quantity); //更新数据

  //取出字符串中的数字
  // $balance=trim($balance);
  // if(empty($balance)){return '';}
  // $temp=array('1','2','3','4','5','6','7','8','9','0');
  // $result='';
  // for($i=0;$i<strlen($balance);$i++){
  //       if(in_array($balance[$i],$temp)){
  //           $result.=$balance[$i];
  //       }
  //   }
    //验证成功
    if($state==0){
      //验证订单号
      $transaction_id = $k['receipt']['in_app'][0]['transaction_id'];
      if($transaction_id==$transaction_Identifier){
        //成功后更改purchase数据状态
        $this->Ff_purchase->id = $purchaseid;
        $post = $this->Ff_purchase->read();
        if(!$post){
          $this->stopProgram(array('success' => 0,'message' => '用户不存在！'));
        }
        $this->Ff_purchase->saveField('state', 1); //更新数据

        $message='充值成功';
        echo json_encode (array('success' => 1,'message' => $message));
      }
    }else{
      //失败后扣除用户余额，用户余额出现负数发出邮件通知
      $this->Ff_user->id = $uid;
      $post = $this->Ff_user->read();  //读取数据
      if(!$post){
        $this->stopProgram(array('success' => 0,'message' => '用户不存在！'));
      }
      $this->Ff_user->saveField('Balance', $post['Ff_user']['Balance']-$balance*$quantity); //更新数据
      $this->Ff_user->id = $uid;
      $balance = $this->Ff_user->read();
      if(!$balance){
        $this->stopProgram(array('success' => 0,'message' => '用户不存在！'));
      }
      //帐号异常后发送邮件
      if($balance['Ff_user']['Balance']<0){
        $this->send_email($balance['Ff_user']['Username']);
      }

      //失败后更改purchase数据状态
      $this->Ff_purchase->id = $purchaseid;
      $post = $this->Ff_purchase->read();
      if(!$post){
        $this->stopProgram(array('success' => 0,'message' => '用户不存在！'));
      }
      $this->Ff_purchase->saveField('state', $state); //更新数据

      $message='充值失败！';
      echo json_encode(array('success' => 0,'message' => $message));
    }

    $this->log($this->request->here.$message);
    exit();

}

//产品
  public function Products (){

    $params  = $this->request->data;
    $message = '';
    $this->checkParams(array("soft"));

    if($params['soft']){

          $soft = $this->Ff_software->find('first',array(
              'conditions'    =>array(
                  'software_type' =>$params['soft']
                  )
            ));
            if(count($soft)==0){
              $this->stopProgram(array('success' => 0,'message' => '软件类型不存在！'));
            }

            $updatetime = 0;
            if(array_key_exists('updatetime',$params)){
                $updatetime = $params['updatetime'];
            }

            $type_value = $soft['Ff_software']['software_type_value'];

            $productArray = array();

            $user = $this->Ff_product->find('all',array(
              'conditions' => array(
                      'software_type_value' => $type_value,
                      'update_time > '      => $updatetime
                    )
                )
               );

        if(count($user)==0){
          $this->stopProgram(array('success' => 0,'message' => '数据不存在！'));
        }
        foreach ($user as $key => $value) {

				$products = $value['Ff_product'];

				$priceArray = array();

        $res = $this->Ff_price->find('all',array(
          'conditions' => array(
                  'product_id' => $value['Ff_product']['Id']

                )
            )
           );
           if(count($res)==0){
             $this->stopProgram(array('success' => 0,'message' => '数据不存在！'));
           }
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
      echo json_encode(array('success' => 1,'message' => $message,'data'=>$productArray));
    }
    else{

      $message = '非法参数';
      echo json_encode(array('success' => 0,'message' => $message));
    }

      $this->log($this->request->here.$message);
      exit();

  }
//GetProductPriceById
public function Price(){

  $params = $this->request->data;
  $message = '';
  $this->checkParams(array("product_id"));

  $productArray = array();

  if ($params['product_id']) {
  			$res = $this->Ff_price->query("SELECT * FROM `ff_prices` AS `p` LEFT JOIN `ff_effectives` AS `e` ON `p`.effective_id=`e`.id WHERE  `p`.product_id = ".$params['product_id']);
        if(count($res)==0){
          $this->stopProgram(array('success' => 0,'message' => '数据不存在！'));
        }
        foreach ($res as $key => $value) {
          $temp = array_merge($value['p'],$value['e']);
          $temp['price_id'] = $value['p']['Id'];
          array_push($productArray,$temp);
        }

        $message = '获取价格成功！';
  			echo json_encode(array('success' => 1,'message' => $message ,'data' => $productArray));

  		} else {

        $message = '非法参数！';
  			echo json_encode(array('success' => 0,'message' => $message));

  		}

      $this->log($this->request->here.$message);
      exit();

}
//UserProducts
public function UserProducts(){
    $params = $this->request->data;
    $message = '';
    $this->checkParams(array("phone_number"));
    $productArray=array();

    $phoneNumber = $params['phone_number'];
    $res = $this->Ff_user->find('first',array(
      'conditions' => array(
        'Phone_number' => $phoneNumber
            )
    ));
    if($res){
        $this->stopProgram(array('success' => 0,'message' => '用户不存在！'));
    }

    $uid = $res['Ff_user']['Id'];

    $re = $this->Ff_expiredtime->find('all',array(
          'conditions' => array(
            'user_id' => $uid
                )
        ));
    if(count($re)==0){
      $this->stopProgram(array('success' => 0,'message' => '数据不存在！'));
    }

    foreach ($re as $key => $value) {
          $temp = array_merge($value['Ff_expiredtime']);
          array_push($productArray,$temp);
      }


        if($res){
          $message = '查询成功！';
          echo json_encode(array('success' => 1,'message' => $message,'data'=>$productArray));
        }else{
          $message = '查询失败，没有找到相关信息！';
          echo json_encode(array('success' => 0,'message' => $message));
        }

        $this->log($this->request->here.$message);
        exit();



}

//RegistProduct
public function RegistProduct(){

  $params = $this->request->data;
  $message = '';
  $this->checkParams(array("phone_number",'purchases_time','product_id','effective_type'));
  $this->getAppInfo();
  $productArray = array();
  $effective_time = time();
  $phoneNumber = $params['phone_number'];
  $purchases_time = $params['purchases_time'];

  /**
  判断上传时间戳，如果相同返回对应的数据（防止重复注册）
  */
  $pt = $this->Ff_regist->find('first',array(
    'conditions' => array(
      'purchases_time' => $purchases_time
          )
  ));

  if($pt){
    $message = '购买成功！';
    echo json_encode(array('success' => 1,'message' => $message,'data'=>$pt['Ff_regist']));
    $this->log($this->request->here.$message);
    exit();
  }

  $res = $this->Ff_user->find('first',array(
    'conditions' => array(
      'Phone_number' => $phoneNumber
          )
  ));

  if(!$res){
    $this->stopProgram(array('success' => 0,'message' => '用户不存在！'));
  }

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
  if(!$effRes){
    $this->stopProgram(array('success' => 0,'message' => '卡类型不存在！'));
  }
  $days = $effRes['Ff_effective']['days'];//天数

  $price = $this->Ff_price->find('first',array(
    'conditions' => array(
      'product_id'   => $params['product_id'],
      'effective_id' => $params['effective_type']
          )
  ));
  if(!$price){
    $this->stopProgram(array('success' => 0,'message' => '该科目未配置对应的卡类型！'));
  }
  $effprice = $price['Ff_price']['product_price'];//科目价格
  $expire_time = $effective_time + $days*24*60*60;
  $effective_type = $params['effective_type'];

   $user = $this->Ff_user->findById($uid);

   if(!$user){
     $this->stopProgram(array('success' => 0,'message' => '用户不存在！'));
   }

          //如果积分足够先扣积分
          if($user['Ff_user']['Score']-$effprice>=0){

            $user['Ff_user']['Score']=$user['Ff_user']['Score']-$effprice;

            if ($this->Ff_user->save(array('id'=>$uid,'Score'=>$user['Ff_user']['Score']))) {

              $data = array('user_id'=>$uid,'product_id'=>$params['product_id'],
                                    'purchases_time'=>$purchases_time,'valid_time'=>$effective_time,
                                  'expire_time'=>$expire_time,'effective_type'=>$effective_type,
                                'consume_score'=>$effprice,'consume_balance'=>0);
              $r = $this->Ff_regist->save($data);

              //购买成功后保存到期时间
              $sql='REPLACE INTO `ff_expiredtimes`
              (`user_id`, `product_id`, `expired_time`)
              VALUES ('.$uid.','.$params['product_id'].','.$expire_time.')';

              $this->Ff_expiredtime->query($sql);


              $message = '购买成功！';
              echo json_encode(array('success' => 1,'message' => $message,'data'=>$r['Ff_regist']));

            }else{
              $message = '购买失败！';
              echo json_encode(array('success' => 0,'message' => $message));

            }

            $this->log($this->request->here.$message);
            exit();

          }else{
            //积分不足扣除全部积分
            $consume_score=$user['Ff_user']['Score'];

            $user['Ff_user']['Score']=$user['Ff_user']['Score']-$effprice;
            $user['Ff_user']['Balance']=$user['Ff_user']['Balance']+$user['Ff_user']['Score'];

            $consume_balance=-$user['Ff_user']['Score'];

            if($user['Ff_user']['Balance']>=0){

              if ($this->Ff_user->save(array('id'=>$uid,'Balance'=>$user['Ff_user']['Balance'],'Score'=>0))) {

                $data = array('user_id'=>$uid,'product_id'=>$params['product_id'],
                                      'purchases_time'=>$purchases_time,'valid_time'=>$effective_time,
                                    'expire_time'=>$expire_time,'effective_type'=>$effective_type,
                                  'consume_score'=>$consume_score,'consume_balance'=>$consume_balance);
                $r = $this->Ff_regist->save($data);


                //购买成功后保存到期时间
                $sql='REPLACE INTO `ff_expiredtimes`
                (`user_id`, `product_id`, `expired_time`)
                VALUES ('.$uid.','.$params['product_id'].','.$expire_time.')';

                $this->Ff_expiredtime->query($sql);


                $message = '购买成功！';
                echo json_encode(array('success' => 1,'message' => $message,'data'=>$r['Ff_regist']));

              }else{

                $message = '购买失败！';
                echo json_encode(array('success' => 0,'message' => $message));

              }

              $this->log($this->request->here.$message);
              exit();

            }else{

              $message = '购买失败！';
              echo json_encode(array('success' => 0,'message' => $message));

            }
            $this->log($this->request->here.$message);
            exit();
          }

      $this->log($this->request->here.$message);
      exit();

}

//赠送积分私有接口
public function PresentScore(){

     $params = $this->request->data;
     $message = '';
     $this->checkParams(array("phone_number",'softe'));
     $phone_number = $params['phone_number'];
     $soft = $params['soft'];

     //当前日期时间戳（不含分秒）
     $time = strtotime(date("Y-m-d",time()));
     $s=$this->Ff_software->find('first',array(
       'conditions' => array(
         'software_type' => $soft
       )
     ));
     if(!$s){
       $this->stopProgram(array('success' => 0,'message' =>'软件类型不存在！'));
     }
    //执行SQL语句
     $sql = 'SELECT `Ff_score`.`type`,`Ff_score`.`description`,`Ff_score`.`id`,`Ff_score`.`type`, `Ff_score`.`days`, `Ff_score`.`score`, `Ff_score`.`software_type_value`
     FROM `fenfen`.`ff_scores` AS `Ff_score`
     WHERE software_type_value & '.$s['Ff_software']['software_type_value'].' != 0
     AND type = '.$params['type'].' ';

     $rule = $this->Ff_score->query($sql);

     if(array_key_exists(0,$rule)){
       $rule = $rule[0];
     }else{
       $message = '软件类型不存在！';
       $this->log($this->request->here.$message);
       exit();
     }

     $ruletime = $rule['Ff_score']['days'];

     $user = $this->Ff_user->find('first',array(
              'conditions' => array(
              'Phone_number' => $phone_number
            )
        ));
    if(!$user){
      $this->stopProgram(array('success' => 0,'message' =>'用户不存在！'));
    }

     $user = $user['Ff_user'];

     $present=$this->Ff_present->find('first',array(
       'conditions' => array(
         'user_id' => $user['Id'],
         'type' => $rule['Ff_score']['type']
       ),
       'order' => array('Ff_present.present_time' => 'desc')
         ));


        if($present){
          //操作过快会有BUG
         if($time-$present['Ff_present']['present_time']>=($ruletime*60*60*24)){
          //保存赠送积分历史
           $this->Ff_present->save(array(
           'user_id'        =>$user['Id'],
           'present_time'   =>$time,
           'score'          =>$rule['Ff_score']['score'],
           'description'    =>$rule['Ff_score']['description'],
           'type'           =>$rule['Ff_score']['type']
          ));

           $user['Score'] = $user['Score']+$rule['Ff_score']['score'];

           $this->Ff_user->save(array('id'=>$user['Id'],'Score'=>$user['Score']));

           $message = '积分领取成功！';
           echo json_encode(array(
           'success'     => 1,
           'message'     => $message,
           'data'        =>$user,
           'score'       =>$rule['Ff_score']['score'],
           'description' =>$rule['Ff_score']['description']
          ));

         }else{

           $message = '您已经获得该积分,不能重复领取！';
           echo json_encode(array('success' => 0,'message' => $message));

         }

        $this->log($this->request->here.$message);
        exit();

       }else{
         //保存赠送积分历史
         $this->Ff_present->save(array(
           'user_id'      =>$user['Id'],
           'present_time' =>$time,
           'score'        =>$rule['Ff_score']['score'],
           'description'  =>$rule['Ff_score']['description'],
           'type'         =>$rule['Ff_score']['type']
         ));

         $user = $this->Ff_user->findById($user['Id']);
         if(!$user){
           $this->stopProgram(array('success' => 0,'message' =>'用户不存在！'));
         }

         $user = $user['Ff_user'];

         $user['Score'] = $user['Score']+$rule['Ff_score']['score'];

         $this->Ff_user->save(array('id'=>$user['Id'],'Score'=>$user['Score']));

         $message = '积分领取成功！';
         echo json_encode(array(
         'success'     => 1,
         'message'     => $message,
         'data'        =>$user,
         'score'       =>$rule['Ff_score']['score'],
         'description' =>$rule['Ff_score']['description']
        ));
       }

       $this->log($this->request->here.$message);
       exit();

}
//注册用户，重置密码验证
public function RegistCheck (){

  $params = $this->request->data;
  $message = '';

  $this->checkParams(array("phone_number",'password','checkcode'));
  $phone_number = $params['phone_number'];
  $password = $params['password'];
  $checkcode = $params['checkcode'];
  $name = $params['phone_number'];
  //过期时间
  $overduedays = 1*60*60;

  $imei ='';
  $software_type = '';
  $type = '';

  if(array_key_exists('imei',$params)){
      $imei = $params['imei'];
  }
  if(array_key_exists('soft',$params)){
      $software_type = $params['soft'];
  }
  if(array_key_exists('type',$params)){
      $type = $params['type'];
  }
  //待定字段
  if(array_key_exists('name',$params)){
      $name = $params['name'];
  }
  $present_time = time();


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
        echo json_encode(array('success' => 0,'message' => $message));
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

         //注册时查询是否有分享记录
         $Score = 0;
         $result=ClassRegistry::init(array(
           'class' => 'ff_sharescores', 'alias' => 'sharescores'));
         $r = $result->find('first',array(
           'conditions' => array(
             'phone_number' => $phone_number
           )
         ));

         if($r){
           //领取分享赠送的10积分
           $Score = $r['sharescores']['score'];
           //查询score表对应的数据type表示类型 8（您的朋友已使用优惠卷）
           $k = $this->Ff_score->find('first',array(
             'conditions' => array(
               'type' => 8
             )
           ));
           if(!$k){
             $this->stopProgram(array('success' => 0,'message' =>'积分类型不存在!'));
           }
           //增加分享者的积分
           $this->Ff_user->id = $r['sharescores']['senter_user_id'];
           $post = $this->Ff_user->read();  //读取数据
           if(!$post){
             $this->stopProgram(array('success' => 0,'message' =>'用户不存在!'));
           }
           $this->Ff_user->saveField('Score', $post['Ff_user']['Score']+$k['Ff_score']['score']); //更新数据
           //赠送成功后保存赠送记录
           $this->Ff_present->save(array(
             'user_id'      =>$post['Id'],
             'present_time' =>time(),
             'score'        =>$Score,
             'description'  =>'成功领取优惠卷！',
             'type'         =>9
           ));
           //使用完清空id
           $this->Ff_user->clear();
         }

     if ($user) {
      $message = '此手机号已存在！';
      $result = array('success' => 0,'message' => $message);
     } else {

     $this->Ff_user->save(array('Username'=>$name,
     'Phone_number'=>$phone_number,'Password'=>md5($password),
     'Balance'=>0,'Score'=>$Score,'Status'=>1));

     $res = $this->Ff_user->findById($this->Ff_user->id);

     if ($res) {
       /**
       不返回密码
      */
     unset($res['Ff_user']['Password']);

     $result = array('success' => 1,'message' => $message,'data'=>$res['Ff_user']);
     $this->Ff_msgcheck->save(array('id'=>$check['Id'],'status'=>2));
     //注册成功直接登陆，保存登陆信息
     $user_id=$res['Ff_user']['Id'];
     $this->Ff_loginrecord->save(array(
        'user_id'=>$user_id,'software_type'=>$software_type,'type'=>$type,
        'imei'=>$imei,'present_time'=>$present_time));

     } else {
     $message = '注册失败！(系统错误)';
     $result = array('success' => 0,'message' => $message);
     }
    }
     $this->log($message);
     echo json_encode($result);
     exit();

   }else{
     $this->log($this->request->here.$message);
     echo json_encode(array('success' => 0,'message' => '验证码有误！'));
     exit();
   }



}

//注册
    public function Regist(){

      $params = $this->request->data;
      $message = '';
      $this->checkParams(array("phone_number",'flag'));
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
       $result = array('success' => 0,'message' => $message);
      } else {
       $this->Ff_msgcheck->save(array('phone_number'=>$phoneNumber,'checkcode'=>$checkcode,
                                  'present_time'=>$present_time,'status'=>$status,'flag'=>$flag));

       $message = '申请成功！';
       $result = array('success' => 1,'message' => $message);

   }

  $this->stopProgram($result);

}

  //  ModInfo 修改用户名密码
  public function ModInfo() {

    $params = $this->request->data;
    $message = '';

    $oldpwd = '';
    $newpwd = '';
    $name = '';
    $phoneNumber = $params['phone_number'];
    $this->checkParams(array("phone_number"));

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
      					$result = array('success' => 1,'message' => $message,'data' => $res['Ff_user']);
      				} else {
                $message = '修改失败！';
      					$result = array('success' => 0,'message' => $message);
      				}
      			} else {
              $message = '该用户不存在！';
              $result = array('success' => 0,'message' => $message);
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
              $result = array('success' => 1,'message' => $message,'data' => $res['Ff_user']);
            } else {
              $message = '密码修改失败！';
              $result=array('success' => 0,'message' => $message);
            }
          } else {
            $message = '传入信息有误！';
            $result = array('success' => 0,'message' => $message);
          }

    }
        $this->stopProgram($result);

  }
  //发送短信
public function SendMsg(){

    $check = $this->Ff_msgcheck->find('all',array(
      'conditions' => array(
        'status' => 0
      ),
      'order' => array('Ff_msgcheck.present_time' => 'desc')
    ));
    if(count($check)==0){
      $this->stopProgram(array('success' => 0,'message' => '没有查到数据！'));
    }
  $a=array();

  foreach ($check as $key => $value) {
    array_push($a,$value['Ff_msgcheck']);
  }

    echo json_encode(array('success' => 1,'message' => '查询成功！','data' => $a));
    $this->log($this->request->here.'查询成功！');
    exit();

  }
  //发送成功更改数据库状态
  public function MsgStatus(){

    $params = $this->request->data;

    $this->checkParams(array("msgid"));

    $msgid = $params['msgid'];

    $this->Ff_msgcheck->save(array('id'=>$msgid,'status'=>1));

    echo json_encode(array('success' => 1,'message' => '修改成功！'));
    $this->log($this->request->here.'修改成功！');
    exit();

  }
//保持登陆状态
public function KeepLogin(){

      $params = $this->request->data;
      $message = '';
      $this->checkParams(array("user_id","imei",'soft','type'));

      $user_id = $params['user_id'];
      $imei = $params['imei'];
      $software_type = $params['soft'];
      $type = $params['type'];
      $present_time = time();

      $user = $this->Ff_user->findById($user_id);


      if(!$user){
        $this->stopProgram(array('success' => 0,'message' => '验证失败！（传入用户不存在）'));
      }else{

        $user = $user['Ff_user'];

        if($user['Status']==1){
          $message = '验证成功！';

          /**
          不返回密码
          */
          unset($user['Password']);

          $result = array('success' => 1,'message' =>$message ,'data'=>$user);
         //保存登陆记录
          $this->Ff_loginrecord->save(array(
             'user_id'=>$user_id,'software_type'=>$software_type,'type'=>$type,
             'imei'=>$imei,'present_time'=>$present_time));

        }else if($user['Status']==2){
          $message = '验证失败！（用户封禁）';
          $result = array('success' => 0,'message' => $message);
        }else if($user['Status']==3){
          $message = '验证失败！（用户抹除）';
          $result = array('success' => 0,'message' => $message);
        }else{
          $message = '系统错误联系管理员！';
          $result = array('success' => 0,'message' => $message);
        }
      }

      $this->stopProgram($result);

}
//用户余额变更记录分页（充值，消费）
public function RecordPage(){

    $params = $this->request->data;

    $this->checkParams(array("page","phone"));
    $page = $params['page'];
    $phone_number = $params['phone'];
    $limit = 10;
    $result=array();
    $result['success'] =0;

    $res = $this->Ff_user->find('first',array(
      'conditions' => array(
        'Phone_number' => $phone_number
            )
    ));
    if(!$res){
      $result['message'] ='用户不存在！';
      $this->stopProgram($result);
    }

    $uid = $res['Ff_user']['Id'];

    $sql='select * from
(
select 0 action,store_price money,null score,purchases_time time,null text,null card
  from ff_purchases where user_id='.$uid.'
  union all
select 1 action,consume_balance money,consume_score score,purchases_time time,product_name text,description card
  from ff_regists r left join ff_products f on r.product_id=f.id
       left join ff_effectives e on r.effective_type=e.id
where user_id='.$uid.'
  union all
select 2 action,null money,score,present_time time,description text,null card
  from ff_presents where user_id='.$uid.'
) x order by time desc;';
    $re = $this->Ff_user->query($sql);
    $k = array();

    if(count($re)==0){
      $result['success'] =1;
      $result['message'] ='查询成功！没有对应的数据';
      $result['data'] = array();
      $result['count'] = 0;
      $result['pages'] = 1;
      $result['page'] = 0;
      $this->stopProgram($result);
    }
    foreach ($re as $key => $value) {
          $temp = array_merge($value['x']);
          array_push($k,$temp);
        }

    $pagecount = count($k)/$limit;

    $temp=array();
    $pages = 0;
    for ($i=0; $i < $pagecount; $i++) {
        $output = array_slice($k, $limit*$i,$limit);
        $pages = $i+1;
        $temp[$pages] = $output;
    }
      //查询成功
      $result['success'] =1;
      $result['message'] ='查询成功！';
      //数据
      $result['data'] = $temp[$page];
      //记录总数
      $result['count'] = count($k);
      //分页总数
      $result['pages'] = $pages;
      //当前页数
      $result['page'] = $page;

      //返回数据
      $this->stopProgram($result);

}
//第3方登陆接口
public function ThirdPartyLogin(){

  $params = $this->request->query;
  $message = '';
  $result['success'] =0;
  $this->checkParams(array("id","type","nick"));

  //判断是否传入电话号
  if(array_key_exists('phone', $params)){

    $u = $this->Ff_user->find('first',array(
      'conditions' => array(
        'Third_party_id' => $params['id']
      )
    ));
    //判断是否有第3方登陆记录
    if($u){
        //如果有绑定手机号
       $this->Ff_user->save(array(
         'Id'           => $u['Ff_user']['Id'],
         'Phone_number' => $params['phone']
       ));
      $res = $this->Ff_user->findById($this->Ff_user->id);
      $message = '登陆成功！';
      $result = array('success' => 1,'message' => $message,'data' => $res['Ff_user']);

      $this->stopProgram($result);
    }else{
      //如果没有新增user
      $res = $this->Ff_user->save(array(
        'Third_party_id'   => $params['id'],
        'Third_party_type' => $params['type'],
        'Nick'             => $params['nick'],
        'Phone_number'     => $params['type'].$params['phone']
      ));
      $message = '登陆成功！';
      $result = array('success' => 1,'message' => $message,'data' => $res['Ff_user']);

      $this->stopProgram($result);
    }

  }
  //查询没有传入手机号的记录
  $user = $this->Ff_user->find('first',array(
    'conditions' => array(
      'Third_party_id' => $params['id']
    )
  ));

  if($user){
    //如果有判断手机号是否存在
      if($user['Ff_user']['Phone_number']!=''){
        //如果有返回user记录
        $message = '登陆成功！';
        $result = array('success' => 1,'message' => $message,'data' => $user['Ff_user']);
      }else{
        $message = '未绑定手机号！';
        $result = array('success' => 2,'message' => $message);
      }
  }else{
    //如果没有新增user记录返回未绑定手机号状态
    $res = $this->Ff_user->save(array(
      'Third_party_id'   => $params['id'],
      'Third_party_type' => $params['type'],
      'Nick'             => $params['nick']

    ));
    $message = '未绑定手机号！';
    $result = array('success' => 2,'message' => $message);
  }

    $this->stopProgram($result);

}
//分享share
public function Share(){

  $params = $this->request->data;
  $message = '';
  $result['success'] =0;
  $this->checkParams(array("id","phone"));

  //查询score表对应的数据type表示类型 7（您赠送的优惠卷已被领取）
  $score = $this->Ff_score->find('first',array(
    'conditions' => array(
      'type' => 7
    )
  ));
  if(!$score){
    $result['message']='积分类型不存在！';
    $this->stopProgram($result);
  }

  //生成分享记录
  $result=ClassRegistry::init(array(
    'class' => 'ff_sharescores', 'alias' => 'sharescores'));
  $r = $result->save(array(
      'senter_user_id'  =>$params['id'],
      'phone_number'    =>$params['phone'],
      'share_time'      =>time(),
      'score'           =>10
   ));

   //增加分享者的积分
   $this->Ff_user->id = $params['id'];
   $post = $this->Ff_user->read();  //读取数据
   if(!$post){
     $result['message']='用户不存在！';
     $this->stopProgram($result);
   }
   $this->Ff_user->saveField('Score', $post['Ff_user']['Score']+$score['Ff_score']['score']); //更新数据

   $result['message']='积分领取成功！';
   $this->stopProgram($result);

}




}
?>
