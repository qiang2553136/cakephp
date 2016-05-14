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

      $params = $this->request->data;
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
        /**
        登陆返回前10条数据
        */
        $sql='select * from
    (
    select 0 action,store_price money,null score,purchases_time time,null text,null card
    	from ff_purchases where user_id=27
    	union all
    select 1 action,consume_balance money,consume_score score,purchases_time time,product_name text,description card
    	from ff_regists r left join ff_products f on r.product_id=f.id
           left join ff_effectives e on r.effective_type=e.id
    where user_id=27
    	union all
    select 2 action,null money,score,present_time time,description text,null card
    	from ff_presents where user_id=27
    ) x order by time desc limit 0,10;';
        $result = $this->Ff_user->query($sql);
        $k = array();
        foreach ($result as $key => $value) {
              $temp = array_merge($value['x']);
              array_push($k,$temp);
            }

        /**
        不返回密码
        */
        unset($user['Ff_user']['Password']);

        $result = array('success' => 1,'message' =>$message ,'data'=>$user['Ff_user'],
                        'result' => $k
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
  				echo json_encode (array('success' => 1,'message' => $message ));

  			} else {
          $message = '充值失败！';
  				echo json_encode (array('success' => 0,'message' => $message));

  			}
        $this->log($message);
        exit();

  		} else {
        $message = '充值失败！(系统错误)';
  			echo json_encode (array('success' => 0,'message' => $message));


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
  			echo json_encode(array('success' => 1,'message' => $message ,'data' => $productArray));

  		} else {

        $message = '非法参数！';
  			echo json_encode(array('success' => 0,'message' => $message));

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
  			echo json_encode(array('success' => 1,'message' => $message ,'data' => $productArray));

  		} else {
        $message = '非法参数';
  			echo json_encode(array('success' => 0,'message' => $message));

  		}
      $this->log($message);
      exit();
}

//RegistProduct
public function RegistProduct(){

  $params = $this->request->query;
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

   $user = $this->Ff_user->findById($uid);

    if($params['price']<0){
      $message = '价格有误请联系管理员！';
      echo json_encode(array('success' => 0,'message' => $message));
      $this->log($message);
      exit();
    }

          //如果积分足够先扣积分
          if($user['Ff_user']['Score']-$params['price']>=0){

            $user['Ff_user']['Score']=$user['Ff_user']['Score']-$params['price'];

            if ($this->Ff_user->save(array('id'=>$uid,'Score'=>$user['Ff_user']['Score']))) {


              $message = '购买成功！';

              $data = array('user_id'=>$uid,'product_id'=>$params['product_id'],
                                    'purchases_time'=>time(),'valid_time'=>$effective_time,
                                  'expire_time'=>$expire_time,'effective_type'=>$effective_type,
                                'consume_score'=>$params['price'],'consume_balance'=>0);
              $r = $this->Ff_regist->save($data);

              echo json_encode(array('success' => 1,'message' => $message,'data'=>$r['Ff_regist']));

            }else{
              $message = '购买失败！';
              echo json_encode(array('success' => 0,'message' => $message));

            }

            $this->log($message);
            exit();

          }else{
            //积分不足扣除全部积分
            $consume_score=$user['Ff_user']['Score'];

            $user['Ff_user']['Score']=$user['Ff_user']['Score']-$params['price'];
            $user['Ff_user']['Balance']=$user['Ff_user']['Balance']+$user['Ff_user']['Score'];

            $consume_balance=-$user['Ff_user']['Score'];

            if($user['Ff_user']['Balance']>=0){

              if ($this->Ff_user->save(array('id'=>$uid,'Balance'=>$user['Ff_user']['Balance'],'Score'=>0))) {


                $message = '购买成功！';

                $data = array('user_id'=>$uid,'product_id'=>$params['product_id'],
                                      'purchases_time'=>time(),'valid_time'=>$effective_time,
                                    'expire_time'=>$expire_time,'effective_type'=>$effective_type,
                                  'consume_score'=>$consume_score,'consume_balance'=>$consume_balance);
                $r = $this->Ff_regist->save($data);

                echo json_encode(array('success' => 1,'message' => $message,'data'=>$r['Ff_regist']));

              }else{

                $message = '购买失败！';
                echo json_encode(array('success' => 0,'message' => $message));

              }

              $this->log($message);
              exit();

            }else{

              $message = '购买失败！';
              echo json_encode(array('success' => 0,'message' => '购买失败！余额不足'));

            }
            $this->log($message);
            exit();
          }

      $this->log($message);
      exit();

}

//赠送积分

public function PresentScore (){

     $params = $this->request->data;
     $message = '';
     $description = '';
     if(array_key_exists('description',$params)){
       $description = $params['description'];
     }

     $time = time();
     $s=$this->Ff_software->find('first',array(
       'conditions' => array(
         'software_type' => $params['software_type']
       )
     ));
    //执行SQL语句
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

           $this->Ff_present->save(array('user_id'=>$user['Id'],'present_time'=>$time,'score'=>$rule['Ff_score']['score'],'description'=>$description));

           $user['Score'] = $user['Score']+$rule['Ff_score']['score'];

           $this->Ff_user->save(array('id'=>$user['Id'],'Score'=>$user['Score']));

           $message = '积分领取成功！';
           echo json_encode(array('success' => 1,'message' => $message,'data'=>$user));

         }else{

           $message = '您已经获得该积分,不能重复领取！';
           echo json_encode(array('success' => 0,'message' => $message));

         }

        $this->log($message);
        exit();

       }else{
         $this->Ff_present->save(array('user_id'=>$user['Id'],'present_time'=>$time,'score'=>$rule['Ff_score']['score'],'description'=>$description));

         $user = $this->Ff_user->findById($user['Id']);

         $user = $user['Ff_user'];

         $user['Score'] = $user['Score']+$rule['Ff_score']['score'];

         $this->Ff_user->save(array('id'=>$user['Id'],'Score'=>$user['Score']));

         $message = '积分领取成功！';
         echo json_encode(array('success' => 1,'message' => $message,'data'=>$user));

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

     if ($user) {
      $message = '此手机号已存在！';
      $result = array('success' => 0,'message' => $message);
     } else {

     $this->Ff_user->save(array('Username'=>$name,
     'Phone_number'=>$phone_number,'Password'=>md5($password),
     'Balance'=>0,'Score'=>0,'Status'=>1));

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
     $this->log($message);
     echo json_encode(array('success' => 0,'message' => '验证码有误！'));
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
       $result = array('success' => 0,'message' => $message);
      } else {
       $this->Ff_msgcheck->save(array('phone_number'=>$phoneNumber,'checkcode'=>$checkcode,
                                  'present_time'=>$present_time,'status'=>$status,'flag'=>$flag));

       $message = '申请成功！';
       $result = array('success' => 1,'message' => $message);

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

    echo json_encode(array('success' => 1,'message' => '查询成功！','data' => $a));
    $this->log('查询成功！');
    exit();

  }
  //发送成功更改数据库状态
  public function MsgStatus(){

    $params = $this->request->data;

    $msgid = $params['msgid'];

    $this->Ff_msgcheck->save(array('id'=>$msgid,'status'=>1));

    echo json_encode(array('success' => 1,'message' => '修改成功！'));
    $this->log('修改成功！');
    exit();

  }
//保持登陆状态
public function KeepLogin(){

      $params = $this->request->data;
      $message = '';

      $user_id = $params['user_id'];
      $imei = $params['imei'];
      $software_type = $params['soft'];
      $type = $params['type'];
      $present_time = time();

      $user = $this->Ff_user->findById($user_id);


      if(!$user){
        $message = '验证失败！（传入用户不存在）';
        $result = array('success' => 0,'message' => $message);

      }else{

        $user = $user['Ff_user'];

        if($user['Status']==1){
          $message = '验证成功！';
          /**
          登陆返回前10条数据
          */
          $sql='select * from
      (
      select 0 action,store_price money,null score,purchases_time time,null text,null card
      	from ff_purchases where user_id=27
      	union all
      select 1 action,consume_balance money,consume_score score,purchases_time time,product_name text,description card
      	from ff_regists r left join ff_products f on r.product_id=f.id
             left join ff_effectives e on r.effective_type=e.id
      where user_id=27
      	union all
      select 2 action,null money,score,present_time time,description text,null card
      	from ff_presents where user_id=27
      ) x order by time desc limit 0,10;';
          $result = $this->Ff_user->query($sql);
          $k = array();
          foreach ($result as $key => $value) {
                $temp = array_merge($value['x']);
                array_push($k,$temp);
              }

          /**
          不返回密码
          */
          unset($user['Password']);

          $result = array('success' => 1,'message' =>$message ,'data'=>$user,
                          'result' => $k
                        );
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

      $this->log($message);
      echo json_encode($result);
      exit();
}
//用户余额变更记录分页（充值，消费）
public function RecordPage(){


    $params = $this->request->data;
    $page = $params['page'];
    $limit = 10;

    $sql='select * from
(
select 0 action,store_price money,null score,purchases_time time,null text,null card
  from ff_purchases where user_id=27
  union all
select 1 action,consume_balance money,consume_score score,purchases_time time,product_name text,description card
  from ff_regists r left join ff_products f on r.product_id=f.id
       left join ff_effectives e on r.effective_type=e.id
where user_id=27
  union all
select 2 action,null money,score,present_time time,description text,null card
  from ff_presents where user_id=27
) x order by time desc;';
    $re = $this->Ff_user->query($sql);
    $k = array();
    foreach ($re as $key => $value) {
          $temp = array_merge($value['x']);
          array_push($k,$temp);
        }

    $pagecount = count($k)/$limit;

    $temp=array();
    $result=array();
    $pages = 0;
    for ($i=0; $i < $pagecount; $i++) {
        $output = array_slice($k, $limit*$i,$limit);
        $pages = $i+1;
        $temp[$pages] = $output;
    }

    $result['data'] = $temp[$page];
    //记录总数
    $result['count'] = count($k);
    //分页总数
    $result['pages'] = $pages;
    //当前页数
    $result['page'] = $page;

    echo json_encode($result);
    exit();

}



}
?>
