<?php

/***********************************************************************
Orchard Hut Online Shop
Copyright (C) 2013-2015  Kazuichi Takashiro

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.

takashiro@qq.com
************************************************************************/

if(!defined('IN_ADMINCP')) exit('access denied');

class GeTuiModule extends AdminControlPanelModule{

	public function defaultAction(){
		$this->sendAction();
	}

	public function configAction(){
		$fields = array('app_id', 'app_key', 'master_secret', 'host');

		if($_POST){
			$config = array();
			foreach($fields as $f){
				$config[$f] = isset($_POST['getuiconfig'][$f]) ? trim($_POST['getuiconfig'][$f]) : '';
			}
			writedata('getuiconfig', $config);
			showmsg('edit_succeed', 'refresh');
		}

		$getuiconfig = readdata('getuiconfig');
		foreach($fields as $f){
			isset($getuiconfig[$f]) || $getuiconfig[$f] = '';
		}

		extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
		include view('getui_config');
	}

	public function sendAction(){
		if($_POST){
			$config = readdata('getuiconfig');

			require_once S_ROOT.'plugin/getui/IGt.Push.php';
			try{
				$igt = new IGeTui($config['host'], $config['app_key'], $config['master_secret']);
			}catch(Exception $e){
				showmsg('getui_configuration_error', 'back');
			}


			$template = new IGtNotificationTemplate;
			$template->set_appId($config['app_id']);
			$template->set_appkey($config['app_key']);
			$template->set_title($_POST['title']);
			$template->set_text($_POST['content']);
			$template->set_logo('http://weifruit.cn/view/user/default/image/icon_watermelon2.png');
			$template->set_isRing(true);//是否响铃
			$template->set_isVibrate(true);//是否震动
			$template->set_isClearable(true);//通知栏是否可清除

			$message = new IGtAppMessage;
			$message->set_isOffline(true);//是否离线
			$message->set_appIdList(array($config['app_id']));
			$message->set_offlineExpireTime(3600 * 12 * 1000);//离线时间
			$message->set_data($template);//设置推送消息类型
			$message->set_PushNetWorkType(0);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送

			try{
				$rep = $igt->pushMessageToApp($message);
				showmsg($rep['result']);
			}catch(Exception $e){
				showmsg($e->getMessage().var_export($config, true));
			}
			exit;
		}

		extract($GLOBALS, EXTR_REFS | EXTR_SKIP);
		include view('getui_send');
	}
}

?>
