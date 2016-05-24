<?php
class SharedMem{
	//刷新缓存
	public static function refeshApplications()
	{
		$result = Cache::read('apps', 'long');
        if (!$result) {
					$result=ClassRegistry::init(array(
						'class' => 'ff_apps', 'alias' => 'app'));
						$datasource=$result->find('all');
						Cache::write('apps', $datasource, 'long');
        }
		return true;
	}
	//获取应用信息
	public static function getAppInfoById($appId=null,$level=0)
	{
		$result = Cache::read('apps');

		if(!$result)
		{
			//第一次进入，强制刷新
			if($level==0)
			{
				self::refeshApplications();
				return self::getAppInfoById($appId,1);
			}
			return false;
		}

		if(!$result)
			return false;
		foreach ($result as $value)
		{
			$application=$value['app'];

			if($application['id']==$appId)
			{
				return $application;
			}
		}
		return false;
	}








}
