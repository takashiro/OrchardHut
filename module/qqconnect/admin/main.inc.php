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

class QQConnectMainModule extends AdminControlPanelModule{

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$qqconnect = readdata('qqconnect');
		foreach(array('appid', 'appkey', 'scope', 'errorReport', 'storageType', 'host', 'user', 'password', 'database') as $var){
			isset($qqconnect[$var]) || $qqconnect[$var] = '';
			isset($_POST['qqconnect'][$var]) && $qqconnect[$var] = $_POST['qqconnect'][$var];
		}

		if($_POST){
			writedata('qqconnect', $qqconnect);
			showmsg('successfully_updated_qqconnect_config', 'refresh');
		}

		include view('main');
	}

}

?>
