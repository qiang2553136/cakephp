<?php
/*
* To change this template, choose Tools | Templates
* and open the template in the editor.
*/

App::uses('AppController', 'Controller');

class FfUsersController extends AppController{

public $uses = array('Ff_user','Ff_purchase','Ff_product',
                    'Ff_effective','Ff_price','Ff_regist',
                    'Ff_score','Ff_present','Ff_software',
                    'Ff_msgcheck','Ff_loginrecord','Ff_expiredtime'
                    );

// public function test() {
//     // $k = $this->Ff_product->find('all');
//
//     // $this->returnSucc('1',$k);
// }
//登陆
public function Login() {

    $params = $this->checkParams(array("username",'password','imei','soft','type'));
    $this->checkAppInfo($params);
    $password = md5($params['password']);
    $present_time = time();

    // 查找符合条件的用户
    $user = $this->Ff_user->find('first',array(
        'conditions' => array('OR' => array(
              'Phone_number' => $params['username'],
              'username' => $params['username']
            ))
          )
        );

    if (!$user) {
        $this->returnError('用户不存在');
    }
    if ($user['Ff_user']['Password'] != $password) {
        $this->returnError('密码不正确');
    }

    // 生成登陆记录
    $this->Ff_loginrecord->save(array(
       'user_id'=>$user['Ff_user']['Id'],
       'software_type'=>$params['soft'],
       'type'=>$params['type'],
       'imei'=>$params['imei'],
       'present_time'=>time()
     ));

    // 返回去掉密码
    unset($user['Ff_user']['Password']);
    $this->returnSucc('登陆成功', $user['Ff_user']);

}


//验证
public function AppleProving(){

    $params =$this->checkParams(array("receipt",'uid','balance','quantity','transaction_Identifier','purchases_time'));
    $this->checkAppInfo($params);
    //测试服务器
    $res = $this->send_post('https://sandbox.itunes.apple.com/verifyReceipt',array('receipt-data'=>$params['receipt']));
    //正式服务器
    // $res = $this->send_post('https://buy.itunes.apple.com/verifyReceipt',array('receipt-data'=>$params['receipt']));

    //苹果验证返回数据
    $AppleProving = json_decode($res,true);
    if(!$AppleProving){
        $this->returnError('苹果验证未通过！');
    }
    //保存充值信息
    $test = $this->Ff_purchase->save(array(
        'user_id' => $params['uid'],
        'purchases_time' => $params['purchases_time'],
        'store_price' => $params['balance']*$params['quantity'],
        'transaction_Identifier' => $params['transaction_Identifier'],
        'receipt' => $params['receipt']
    ));
    //获取新增充值信息的ID
    $purchaseid = $this->Ff_purchase->id;
    //获取苹果验证的状态
    $state = $AppleProving['status'];
    //增加用户余额
    $this->Ff_user->id = $params['uid'];
    $userData = $this->Ff_user->read();  //读取数据
    if(!$userData){
        $this->returnError('用户不存在！');
    }
    $this->Ff_user->saveField('Balance', $userData['Ff_user']['Balance']+$params['balance']*$params['quantity']); //更新数据
    //验证失败
    if($state!=0){
        //失败后扣除用户余额，用户余额出现负数发出邮件通知
        $this->Ff_user->id = $params['uid'];
        $post = $this->Ff_user->read();  //读取数据
        if(!$post){
          $this->returnError('用户不存在！');
        }
        $this->Ff_user->saveField('Balance', $post['Ff_user']['Balance']-$balance*$quantity); //更新数据
        $this->Ff_user->id = $params['uid'];
        $balance = $this->Ff_user->read();
        if(!$balance){
          $this->returnError('用户不存在！');
        }
        //帐号异常后发送邮件
        if($balance['Ff_user']['Balance']<0){
          $this->send_email($balance['Ff_user']['Username']);
        }

        //失败后更改purchase数据状态
        $this->Ff_purchase->id = $purchaseid;
        $post = $this->Ff_purchase->read();
        if(!$post){
          $this->returnError('用户不存在！');
        }
        $this->Ff_purchase->saveField('state', $state); //更新数据

    }
    //验证订单号
    $transaction_id = $AppleProving['receipt']['in_app'][0]['transaction_id'];
    if($transaction_id!=$params['transaction_Identifier']){
        $this->returnError('流水号有误!');
    }
    //成功后更改purchase数据状态
    $this->Ff_purchase->id = $purchaseid;
    $post = $this->Ff_purchase->read();
    if(!$post){
        $this->returnError('用户不存在！');
    }
    $this->Ff_purchase->saveField('state', 1); //更新数据

    $this->returnSucc('充值成功!',array());

}

//产品
public function Products (){

    $params  = $this->checkParams(array('soft'));
    $this->checkAppInfo($params);
    //查出对应的软件配置
    $soft = $this->Ff_software->find('first',array(
              'conditions'    =>array(
                  'software_type' =>$params['soft']
                  )
            ));
    if(!$soft){
        $this->returnError('软件类型不存在！');
    }
    //有可能传入更新时间字段，如果传入按更新时间获取科目
    $updatetime = 0;
    if(array_key_exists('updatetime',$params)){
        $updatetime = $params['updatetime'];
    }
    //软件类型值
    $type_value = $soft['Ff_software']['software_type_value'];
    $productArray = array();
    $productData = $this->Ff_product->find('all',array(
         'conditions' => array(
              'software_type_value' => $type_value,
              'update_time > '      => $updatetime
            )
        ));
    if(count($productData)==0){
        $this->returnError('数据不存在！');
    }
    //查出的科目拼接价格，卡类型后返回
    foreach ($productData as $key => $value) {
		$products = $value['Ff_product'];
        $priceArray = array();
        //查出科目价格拼接
        $priceData = $this->Ff_price->find('all',array(
          'conditions' => array(
                  'product_id' => $value['Ff_product']['Id']

                )
            )
           );
        $priceArray = array();

        foreach ($priceData as $k => $v) {
             //查出卡类型拼接
            $effectiveData = $this->Ff_effective->find('all',array(
              'conditions' => array(
                      'Id' => $v['Ff_price']['effective_id']
                    )
                )
               );
            $te = ($v['Ff_price']);
            $te['price_id']=$te['Id'];
            foreach($effectiveData as $i => $n){
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
    $this->returnSucc('返回科目成功！',$productArray);
  }
//科目价格
public function Price(){

    $params =  $this->checkParams(array("product_id"));
    $this->checkAppInfo($params);

    $productArray = array();

    $res = $this->Ff_price->query("SELECT * FROM `ff_prices` AS `p` LEFT JOIN `ff_effectives` AS `e` ON `p`.effective_id=`e`.id WHERE  `p`.product_id = ".$params['product_id']);
    if(count($res)==0){
        $this->returnError('数据不存在！');
    }
    foreach ($res as $key => $value) {
        $temp = array_merge($value['p'],$value['e']);
        $temp['price_id'] = $value['p']['Id'];
        array_push($productArray,$temp);
    }
    $this->returnSucc('获取价格成功！',$productArray);
}
//用户科目
public function UserProducts(){

    $params = $this->checkParams(array("phone"));
    $this->checkAppInfo($params);

    $productArray = array();

    $userData = $this->Ff_user->find('first',array(
                'conditions' => array(
                    'Phone_number' => $params['phone']
                    )
                ));

    if(!$userData){
        $this->returnError('用户不存在！');
    }

    $uid = $userData['Ff_user']['Id'];

    $re = $this->Ff_expiredtime->find('all',array(
          'conditions' => array(
            'user_id' => $uid
            )
        ));
    if(count($re)==0){
        $this->returnSucc('查询成功！',array());
    }

    foreach ($re as $key => $value) {
        $temp = array_merge($value['Ff_expiredtime']);
        array_push($productArray,$temp);
      }

    $this->returnSucc('查询成功！',$productArray);

}

//注册科目
public function RegistProduct(){

    $params = $this->checkParams(array("phone",'purchases_time','product_id','effective_type'));
    $this->checkAppInfo($params);

    $productArray   = array();
    //当前时间
    $effective_time = time();

    $purchases_time = $params['purchases_time'];

  /**
  判断上传时间戳，如果相同返回对应的数据（防止重复注册）
  */
    $pt = $this->Ff_regist->find('first',array(
      'conditions' => array(
        'purchases_time' => $purchases_time
            )
    ));
    //如果有记录返回成功和购买信息
    if($pt){
        $this->returnSucc('购买成功！',$pt['Ff_regist']);
    }

    $userData = $this->Ff_user->find('first',array(
        'conditions' => array(
          'Phone_number' => $params['phone']
        )
    ));

    if(!$userData){
        $this->returnError('用户不存在！');
    }

    $uid = $userData['Ff_user']['Id'];

    $registData = $this->Ff_regist->query("SELECT * FROM `ff_regists` `r` LEFT JOIN `ff_effectives` `e` ON `e`.id = `r`.effective_type WHERE `r`.user_id=".
    $uid." AND `r`.product_id=".$params['product_id']." ORDER BY `r`.valid_time DESC");

    if(count($registData)!=0){
        foreach ($registData as $key => $value) {
            $temp = array_merge($value['e'],$value['r']);
            array_push($productArray,$temp);
        }
        //取出第一条数据
        $lastRecord = $productArray[0];
        //最后记录的到期时间
        $effective_time = $lastRecord['valid_time'] + $lastRecord['days']*60*60*24;

    }
    $effRes = $this->Ff_effective->findById($params['effective_type']);
            if(!$effRes){
                $this->returnError('卡类型不存在！');
            }

            $days = $effRes['Ff_effective']['days'];//天数
            //查询该科目的配置信息
            $price =$this->Ff_price->find('first',array(
                        'conditions' => array(
                            'product_id'   => $params['product_id'],
                            'effective_id' => $params['effective_type']
                        )
                    ));
            if(!$price){
                $this->returnError('该科目未配置对应的卡类型！');
            }

    $score = $price['Ff_price']['product_price'];//科目价格
    $expire_time = $effective_time + $days*24*60*60;
    $effective_type = $params['effective_type'];


    $user = $this->Ff_user->findById($uid);
    if($user['Ff_user']['Balance']+$user['Ff_user']['Score']<$score){
        $this->returnError('购买失败！余额不足');
    }
    //如果积分足够先扣积分
    if($user['Ff_user']['Score']-$score>=0){
        $consume_balance=0;
        $consume_score=$score;
        $user['Ff_user']['Score']=$user['Ff_user']['Score']-$score;
        $this->Ff_user->save(array('id'=>$uid,'Score'=>$user['Ff_user']['Score']));
    }else{
        //积分不足扣除全部积分
        $consume_score=$user['Ff_user']['Score'];
        $user['Ff_user']['Score']=$user['Ff_user']['Score']-$score;
        $user['Ff_user']['Balance']=$user['Ff_user']['Balance']+$user['Ff_user']['Score'];
        //消费余额
        $consume_balance=-$user['Ff_user']['Score'];
        $this->Ff_user->save(array('id'=>$uid,'Balance'=>$user['Ff_user']['Balance'],'Score'=>0));
    }
    $data = array(
            'user_id'=>$uid,
            'product_id'=>$params['product_id'],
            'purchases_time'=>$purchases_time,
            'valid_time'=>$effective_time,
            'expire_time'=>$expire_time,
            'effective_type'=>$effective_type,
            'consume_score'=>$consume_score,
            'consume_balance'=>$consume_balance
            );
    //保存充值记录
    $registData = $this->Ff_regist->save($data);

    //购买成功后保存到期时间
    $sql='REPLACE INTO `ff_expiredtimes`
        (`user_id`, `product_id`, `expire_time`)
        VALUES ('.$uid.','.$params['product_id'].','.$expire_time.')';

    $this->Ff_expiredtime->query($sql);


    $this->returnSucc('购买成功！',$registData['Ff_regist']);

}

//赠送积分私有接口
public function PresentScore() {

    $params = $this->checkParams(array("phone",'soft','type'));
    // $this->checkAppInfo($params);

    $now = time();
    //当前日期时间戳（不含分秒）
    $day = strtotime(date("Y-m-d",$now));

    $value = $this->Ff_software->find('first',array(
        'conditions' => array(
            'software_type' => $params['soft']
        )
      ));
    if(!$value) {
        $this->returnError('未知的客户端应用');
    }

    $user = $this->Ff_user->find('first',array(
            'conditions' => array('Phone_number' => $params['phone'])
        ));
    if(!$user){
        $this->returnError('用户不存在！');
    }
    $user = $user['Ff_user'];

    $sql = 'SELECT`Ff_score`.`times`, `Ff_score`.`type`,`Ff_score`.`description`,`Ff_score`.`id`,`Ff_score`.`type`, `Ff_score`.`days`, `Ff_score`.`score`, `Ff_score`.`software_type_value`
    FROM `ff_scores` AS `Ff_score`
    WHERE software_type_value & '.$value['Ff_software']['software_type_value'].' != 0
    AND type = '.$params['type'].' ';

    $scoreData = $this->Ff_score->query($sql);
    if(array_key_exists(0,$scoreData)){
        $scoreData = $scoreData[0]['Ff_score'];
    }else{
        $this->returnError('优惠活动已停止或未开始');
    }

    $limitDays  = $scoreData['days'];
    $limitTimes = $scoreData['times'];

    // 如果次数限制大于 0 则查询符合限制的次数是否超出
    if ($limitTimes > 0) {
        // 如果限制天数为 0 表示 总限制次数
        $limitTimestamp = $limitDays == 0 ? 0 : $day - ($limitDays * 24 * 60 * 60);

        $presentCount=$this->Ff_present->find('count',array(
            'conditions' => array(
                'user_id' => $user['Id'],
                'type' => $scoreData['type'],
                'present_day > ' => $limitTimestamp
            ),
            'order' => array('Ff_present.present_time' => 'desc')
        ));

        if ($presentCount >= $limitTimes) {
            $this->returnError($scoreData['description'].'每'.$limitDays.'天 最多'.$limitTimes.'次');
        }
    }
    // 插入积分记录
    //保存赠送积分历史
    $this->Ff_present->clear();
    $this->Ff_present->save(array(
     'user_id'        =>$user['Id'],
     'present_time'   =>$now,
     'present_day'    =>$day,
     'score'          =>$scoreData['score'],
     'description'    =>$scoreData['description'],
     'type'           =>$scoreData['type']
    ));

    $this->Ff_user->id = $user['Id'];
    $userData = $this->Ff_user->read();
    $this->Ff_user->saveField('Score', $userData['Ff_user']['Score']+$scoreData['score']);

    // 返回插入成功
    $user['score']       = $scoreData['score'];
    $user['description'] = $scoreData['description'];
    $this->returnSucc($scoreData['description'].'已领取', $user);

}
//注册用户，重置密码验证
public function RegistCheck (){

    $params = $this->checkParams(array("phone",'password','checkcode'));
    $this->checkAppInfo($params);
    //用户名默认是手机号
    $name = $params['phone'];
    $imei ='';
    $software_type = '';
    $type = '';
    //过期时间
    $overduedays = 1*60*60;
    //如果传入了name则用传入值
    if(array_key_exists('name',$params)){
        $name = $params['name'];
    }
    if(array_key_exists('imei',$params)){
        $imei = $params['imei'];
    }
    if(array_key_exists('soft',$params)){
        $software_type = $params['soft'];
    }
    if(array_key_exists('type',$params)){
        $type = $params['type'];
    }
    //当前时间
    $present_time = time();
    $day = strtotime(date("Y-m-d",$present_time));

    $check = $this->Ff_msgcheck->find('first',array(
        'conditions' => array(
            'phone_number' => $params['phone'],
            'status' =>1
        ),
        'order' => array('Ff_msgcheck.present_time' => 'desc')
    ));

    if(!$check){
        $this->returnError('验证码有误！请重新申请！');
    }
    if($check['Ff_msgcheck']['checkcode']!=$params['checkcode']){
        if($check['Ff_msgcheck']['times']>3){
            $this->Ff_msgcheck->id=$check['Ff_msgcheck']['Id'];//设置id
            $msgcheck = $this->Ff_msgcheck->read();//读取数据
            $this->Ff_msgcheck->saveField('status', 2);//更新数据
            $this->Ff_msgcheck->clear();
            $this->returnError('连续输错3次，验证码失效，请重新申请！');
        }
        $this->Ff_msgcheck->id=$check['Ff_msgcheck']['Id'];//设置id
        $msgcheck = $this->Ff_msgcheck->read();//读取数据
        $this->Ff_msgcheck->saveField('times', $msgcheck['Ff_msgcheck']['times']+1);//更新数据
        $this->Ff_msgcheck->clear();
        $this->returnError('验证码有误！');
    }
    $check = $check['Ff_msgcheck'];
    if($check['flag']==0){
        $message = '注册成功！';
    }else{
        $message = '重置密码成功！';
    }

    if(time()-$check['present_time']>$overduedays){
        $this->returnError('验证码失效，请重新申请！');
    }
    //查询是否有用户名
    $user=$this->Ff_user->find('first',array(
       'conditions' => array(
             'Phone_number' => $params['phone']
           )
    ));

    if($user){
        //如果存在用户则修改用户的密码
        $this->Ff_user->id=$user['Ff_user']['Id'];
        $post = $this->Ff_user->read();
        $this->Ff_user->saveField('Password', md5($params['password'])); //更新数据
        $this->Ff_user->id=$user['Ff_user']['Id'];
        $updateUser = $this->Ff_user->read();
        $this->returnSucc($message,$updateUser['Ff_user']);
    }
    //新增用户
    $this->Ff_user->save(array(
        'Username'      => $name,
        'Phone_number'  => $params['phone'],
        'Password'      => md5($params['password'])
    ));
    //查出新增数据
    $userData = $this->Ff_user->findById($this->Ff_user->id);
    //更改验证短信状态
    $this->Ff_msgcheck->save(array('id'=>$check['Id'],'status'=>2));
    //注册成功直接登陆，保存登陆信息
    $this->Ff_loginrecord->save(array(
        'user_id'       => $userData['Ff_user']['Id'],
        'software_type' => $software_type,
        'type'          => $type,
        'imei'          => $imei,
        'present_time'  => $present_time
    ));
    //注册成功赠送10积分
    $s = $this->Ff_score->find('first',array(
          'conditions' => array(
            'type' => 0
            )
        ));

    if($s){
        $Score = $s['Ff_score']['score'];
           //赠送成功后保存赠送记录
        $this->Ff_present->save(array(
            'user_id'      =>$userData['Ff_user']['Id'],
            'present_time' =>time(),
            'present_day'  =>$day,
            'score'        =>$Score,
            'description'  =>$s['Ff_score']['description'],
            'type'         =>$s['Ff_score']['type']
        ));
        $this->Ff_user->id=$userData['Ff_user']['Id'];
        $post = $this->Ff_user->read();
        $this->Ff_user->saveField('Score', $post['Ff_user']['Score']+$Score); //更新数据
        $this->Ff_present->clear();
        $this->Ff_user->clear();
    }
    //注册时查询是否有分享记录
    $share=ClassRegistry::init(array(
            'class' => 'ff_sharescores',
            'alias' => 'sharescores'
            ));
    $r =$share->find('first',array(
                    'conditions' => array(
                        'phone_number' => $params['phone']
                    )
        ));
    if(!$r){
        $userData=$this->Ff_user->findById($userData['Ff_user']['Id']);
        //不返回密码
        unset($userData['Ff_user']['Password']);
        $this->returnSucc($message,$userData['Ff_user']);
    }
    //领取分享赠送的10积分
    $Score = $r['sharescores']['score'];
    $this->Ff_user->id=$userData['Ff_user']['Id'];
    $post = $this->Ff_user->read();  //读取数据
    $this->Ff_user->saveField('Score', $post['Ff_user']['Score']+$Score); //更新数据
    //赠送成功后保存赠送记录
    $this->Ff_present->save(array(
            'user_id'      =>$post['Ff_user']['Id'],
            'present_time' =>time(),
            'present_day'  =>$day,
            'score'        =>$Score,
            'description'  =>'成功领取优惠卷！',
            'type'         =>9
    ));
    $this->Ff_present->clear();
    $this->Ff_user->clear();
    //查询score表对应的数据type表示类型 8（您的朋友已使用优惠卷）
    $scoreData = $this->Ff_score->find('first',array(
            'conditions' => array(
                'type' => 8
                )
            ));
    if($scoreData){
        //增加分享者的积分
        $this->Ff_user->id = $r['sharescores']['senter_user_id'];
        $post = $this->Ff_user->read();  //读取数据
        if(!$post){
            $this->returnError('用户不存在！');
        }
        $this->Ff_user->saveField('Score', $post['Ff_user']['Score']+$scoreData['Ff_score']['score']); //更新数据
        //保存增加积分记录
        $this->Ff_present->save(array(
            'user_id'      =>$post['Ff_user']['Id'],
            'present_time' =>time(),
            'present_day'  =>$day,
            'score'        =>$scoreData['Ff_score']['score'],
            'description'  =>$scoreData['Ff_score']['description'],
            'type'         =>$scoreData['Ff_score']['type']
        ));
        //使用完清空id
        $this->Ff_user->clear();
    }

    //不返回密码
    unset($userData['Ff_user']['Password']);
    $this->returnSucc($message,$this->Ff_user->findById($userData['Ff_user']['Id']));

}

//注册申请
public function Regist(){

    $params = $this->checkParams(array("phone",'flag'));
    $this->checkAppInfo($params);
    //生成4位随机数
    $checkcode = rand(1000,9999);
    //当前时间
    $present_time = time();

    $user=$this->Ff_user->find('first',array(
        'conditions' => array(
          'Phone_number' => $params['phone']
            )
        ));

    if ($user) {
       $this->returnError('此手机号已经注册！');
    }
    $this->Ff_msgcheck->save(array(
        'phone_number'      => $params['phone'],
        'checkcode'         => $checkcode,
        'present_time'      => $present_time,
        'status'=>0,'flag'  => $params['flag']
    ));

    $this->returnSucc('申请成功！','');

}

  //  ModInfo 修改用户名密码
  public function ModInfo() {

    $params = $this->checkParams(array("phone"));

    $oldpwd = '';
    $newpwd = '';
    $name = '';

    if(array_key_exists('oldpwd',$params)){
        $oldpwd = $params['oldpwd'];
    }

    if(array_key_exists('newpwd',$params)){
        $newpwd = $params['newpwd'];
    }

    if(array_key_exists('nick',$params)){
        $name = $params['nick'];
    }


    $userData=$this->Ff_user->find('first',array(
      'conditions' => array(
        'Phone_number' => $params['phone']
        )
    ));
    if(!$userData){
        $this->returnError('用户不存在！');
    }
    if($userData['Ff_user']['Password']!=MD5($oldpwd)){
      $this->returnError('旧密码有误！请重新输入');
    }
    //传入name修改nick字段
    if($name!=''){
		$data=array('id'=>$userData['Ff_user']['Id'],'Nick'=>$name);
        $this->Ff_user->save($data);
        $res = $this->Ff_user->findById($userData['Ff_user']['Id']);
        $this->Ff_user->clear();
        if(!$res){
            $this->returnError('修改失败！');
        }
    }
    //修改密码
    if($newpwd!='' && $oldpwd!=''){
        $data=array('id'=>$userData['Ff_user']['Id'],'Password'=>md5($newpwd));
        $this->Ff_user->save($data);
        $res = $this->Ff_user->findById($userData['Ff_user']['Id']);
        $this->Ff_user->clear();
        if(!$res){
            $this->returnError('修改失败！');
        }
    }
    if(array_key_exists('username',$params)){
        if(!$userData['Ff_user']['Phone_number']==$userData['Ff_user']['Username']){
            $this->returnError('用户名只能修改一次！');
        }
        $username = $params['username'];
        // 查找符合条件的用户
        $user = $this->Ff_user->find('first',array(
            'conditions' => array('OR' => array(
                  'Phone_number' => $username,
                  'Username' => $username
                ))
              )
            );
        if($user){
            $this->returnError('用户名已存在！');
        }
        $data=array('id'=>$userData['Ff_user']['Id'],'Username'=>$username);
        $this->Ff_user->save($data);
        $res = $this->Ff_user->findById($userData['Ff_user']['Id']);
        $this->Ff_user->clear();
        if(!$res){
            $this->returnError('修改失败！');
        }
    }
    if(!$res){
        $this->returnError('缺少关键参数！');
    }
    $this->returnSucc('修改成功！',$res['Ff_user']);

  }

//保持登陆状态
public function KeepLogin(){

    $params = $this->checkParams(array("user_id","imei",'soft','type'));
    $this->checkAppInfo($params);

    $user = $this->Ff_user->findById($params['user_id']);

    if(!$user){
        $this->returnError('用户不存在!');
    }
    $user = $user['Ff_user'];
    //判断用户状态
    switch ($user['Status']) {
        case 1:
            //保存登陆记录
            $this->Ff_loginrecord->save(array(
                'user_id'       => $params['user_id'],
                'software_type' => $params['soft'],
                'type'          => $params['type'],
                'imei'          => $params['imei'],
                'present_time'  => time()
            ));
            break;
        case 2:
            $this->returnError('验证失败！（用户封禁）');
            break;
        case 3:
            $this->returnError('验证失败！（用户抹除）');
            break;
        default:
            $this->returnError('系统错误联系管理员！');
            break;
    }
    unset($user['Password']);
    $this->returnSucc('用户数据已更新！',$user);

}
//用户余额变更记录分页（充值，消费）
public function RecordPage(){

    $params =$this->checkParams(array("page","phone"));

    $this->checkAppInfo($params);

    $limit=10;

    $userData = $this->Ff_user->find('first',array(
      'conditions' => array(
        'Phone_number' => $params['phone']
            )
    ));
    if(!$userData){
        $this->returnError('用户不存在!');
    }

    $uid = $userData['Ff_user']['Id'];

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
    $data = $this->Ff_user->query($sql);
    $k = array();

    if(count($data)==0){
      $result['success'] =1;
      $result['message'] ='查询成功！没有对应的数据';
      $result['data'] = array();
      $result['count'] = 0;
      $result['pages'] = 1;
      $result['page'] = 0;
      echo json_encode($result);
      exit();
    }

    foreach ($data as $key => $value) {
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
      if($pages<$params['page']){
        $result['success'] =1;
        $result['message'] ='查询成功！没有对应的数据';
        $result['data'] = array();
        $result['count'] = 0;
        $result['pages'] = 1;
        $result['page'] = 0;
        echo json_encode($result);
        exit();
      }
      $result['data'] = $temp[$params['page']];
      //记录总数
      $result['count'] = count($k);
      //分页总数
      $result['pages'] = $pages;
      //当前页数
      $result['page'] = $params['page'];

      //返回数据
      echo json_encode($result);
      exit();

}
//第3方登陆接口
public function ThirdPartyLogin(){

    $params = $this->checkParams(array("id","type","nick"));
    $this->checkAppInfo($params);

    $present_time=time();
    $day = strtotime(date("Y-m-d",$present_time));
    //判断是否传入电话号
    if(array_key_exists('phone', $params)){
        //如果没有新增user
        $newUser = $this->Ff_user->save(array(
            'Third_party_id'   => $params['id'],
            'Third_party_type' => $params['type'],
            'Nick'             => $params['nick'],
            'Phone_number'     => $params['type'].$params['phone']
        ));
        //注册成功赠送10积分

        $scoreData = $this->Ff_score->find('first',array(
            'conditions' => array(
              'type' => 0
                )
            ));

        if($scoreData){
             //赠送成功后保存赠送记录
            $this->Ff_present->save(array(
               'user_id'      =>$newUser['Ff_user']['id'],
               'present_time' =>$present_time,
               'present_day'  =>$day,
               'score'        =>$scoreData['Ff_score']['score'],
               'description'  =>$scoreData['Ff_score']['description'],
               'type'         =>$scoreData['Ff_score']['type']
            ));
            $this->Ff_user->id=$newUser['Ff_user']['id'];
            $user = $this->Ff_user->read();
            if(!$user){
               $this->returnError('用户不存在！');
            }
            $this->Ff_user->saveField('Score', $user['Ff_user']['Score']+$scoreData['Ff_score']['score']); //更新数据
            $this->Ff_present->clear();
            $this->Ff_user->clear();
        }
        //注册时查询是否有分享记录
        $share=ClassRegistry::init(array(
                'class' => 'ff_sharescores',
                'alias' => 'sharescores'
                ));
        $r =$share->find('first',array(
                        'conditions' => array(
                            'phone_number' => $params['phone']
                        )
            ));
        if(!$r){
            $u = $this->Ff_user->findById($newUser['Ff_user']['id']);
            $this->returnSucc('登陆成功！',$u['Ff_user']);
        }
        //领取分享赠送的10积分
        $Score = $r['sharescores']['score'];
        $this->Ff_user->id=$newUser['Ff_user']['id'];
        $post = $this->Ff_user->read();  //读取数据
        $this->Ff_user->saveField('Score', $post['Ff_user']['Score']+$Score); //更新数据
        //赠送成功后保存赠送记录
        $this->Ff_present->save(array(
                'user_id'      =>$post['Ff_user']['Id'],
                'present_time' =>$present_time,
                'present_day'  =>$day,
                'score'        =>$Score,
                'description'  =>'成功领取优惠卷！',
                'type'         =>9
        ));
        $this->Ff_present->clear();
        $this->Ff_user->clear();

        //查询score表对应的数据type表示类型 8（您的朋友已使用优惠卷）
        $scoreData = $this->Ff_score->find('first',array(
                'conditions' => array(
                    'type' => 8
                    )
                ));
        if($scoreData){
            //增加分享者的积分
            $this->Ff_user->id = $r['sharescores']['senter_user_id'];
            $post = $this->Ff_user->read();  //读取数据
            if(!$post){
                $this->returnError('用户不存在！');
            }
            $this->Ff_user->saveField('Score', $post['Ff_user']['Score']+$scoreData['Ff_score']['score']); //更新数据
            //保存增加积分记录
            $this->Ff_present->save(array(
                'user_id'      =>$post['Ff_user']['Id'],
                'present_time' =>$present_time,
                'present_day'  =>$day,
                'score'        =>$scoreData['Ff_score']['score'],
                'description'  =>$scoreData['Ff_score']['description'],
                'type'         =>$scoreData['Ff_score']['type']
            ));
            //使用完清空id
            $this->Ff_user->clear();
        }


        $this->Ff_user->id=$newUser['Ff_user']['id'];
        $userData = $this->Ff_user->read();
        $this->Ff_user->clear();
        //不返回密码
        unset($userData['Ff_user']['Password']);
        $this->returnSucc('登陆成功！',$userData['Ff_user']);
    }

    //判断是否有第3方登陆记录
    $userData = $this->Ff_user->find('first',array(
      'conditions' => array(
        'Third_party_id' => $params['id']
      )
    ));

    if(!$userData){
        echo json_encode(array('success' => 2,'message' => '未绑定手机号！'));
        exit();
    }
    unset($userData['Ff_user']['Password']);
    $this->returnSucc('登陆成功！',$userData['Ff_user']);

}
//分享share
public function Share(){

    $params = $this->checkParams(array("id","phone"));
    // $this->checkAppInfo($params);

        $present_time=time();
        $day = strtotime(date("Y-m-d",$present_time));

    //查询score表对应的数据type表示类型 7（您赠送的优惠卷已被领取）
    $score = $this->Ff_score->find('first',array(
      'conditions' => array(
        'type' => 7
      )
    ));
    if(!$score){
        $this->returnError('积分类型不存在！');
    }
   //增加分享者的积分
   $this->Ff_user->id = $params['id'];
   $userData = $this->Ff_user->read();  //读取数据
   if(!$userData){
     $this->returnError('用户不存在！');
   }
   $this->Ff_user->saveField('Score', $userData['Ff_user']['Score']+$score['Ff_score']['score']);
   //保存赠送记录
   $this->Ff_present->save(array(
     'user_id'      =>$params['id'],
     'present_time' =>$present_time,
     'present_day'  =>$day,
     'score'        =>$score['Ff_score']['score'],
     'description'  =>$score['Ff_score']['description'],
     'type'         =>$score['Ff_score']['type']
   ));
    //生成分享记录
    $sharescores=ClassRegistry::init(array(
                'class' => 'ff_sharescores',
                'alias' => 'sharescores'
            ));
    $sharescores->save(array(
        'senter_user_id'  => $params['id'],
        'phone_number'    => $params['phone'],
        'share_time'      => time(),
        'score'           => 10
    ));

   $this->returnSucc('积分领取成功！','');

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

    $k=array();
    foreach ($scoreData as $key => $value) {
        unset($value['Ff_score']['software_type_value']);
        $temp = array_merge($value['Ff_score']);
        array_push($k,$temp);
    }

    $this->returnSucc('查询成功！',$k);

}


}
?>
