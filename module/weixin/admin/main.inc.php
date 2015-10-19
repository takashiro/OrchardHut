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

class WeixinMainModule extends AdminControlPanelModule{

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		$wxconnect_fields = array(
			'app_id', 'app_secret', 'account', 'token', 'aes_key',
			'subscribe_text', 'entershop_keyword', 'bind_keyword', 'bind2_keyword',
			'follow_guide_page',
		);

		if($_POST){
			$wxconnect = array();
			$p = &$_POST['wxconnect'];
			foreach($wxconnect_fields as $var){
				$wxconnect[$var] = isset($p[$var]) ? $p[$var] : '';
			}
			$wxconnect['no_prompt_on_login'] = !empty($p['no_prompt_on_login']);
			$wxconnect['encoding_mode'] = isset($p['encoding_mode']) ? intval($p['encoding_mode']) : WeixinServer::RAW_MESSAGE;
			writedata('wxconnect', $wxconnect);
			showmsg('successfully_updated_wxconnect_config', 'refresh');
		}

		$wxconnect = readdata('wxconnect');
		foreach($wxconnect_fields as $var){
			isset($wxconnect[$var]) || $wxconnect[$var] = '';
		}

		include view('config');
	}

}

?>
