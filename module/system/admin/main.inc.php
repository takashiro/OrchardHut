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

class SystemMainModule extends AdminControlPanelModule{

	public function defaultAction(){
		extract($GLOBALS, EXTR_SKIP | EXTR_REFS);

		@$config = !empty($_POST['system']) ? $_POST['system'] : null;

		@$config = array(
			'sitename' => $config['sitename'],
			'timezone' => intval($config['timezone']),
			'timefix' => intval($config['timefix']),
			'cookiepre' => $config['cookiepre'],
			'refversion' => $config['refversion'],
			'charset' => 'utf-8',
			'style' => $config['style'],
			'debugmode' => !empty($config['debugmode']),
			'log_request' => !empty($config['log_request']),
			'log_error' => !empty($config['log_error']),
			'refresh_template' => !empty($config['refresh_template']),
			'head_element' => htmlspecialchars_decode($config['head_element']),
			'icp' => htmlspecialchars($config['icp']),
			'jquery_cdn' => trim($config['jquery_cdn']),
			'error_report_to' => trim($config['error_report_to']),
		);

		if($_POST){
			writedata('config', $config);
			showmsg('successfully_updated_system_config', 'refresh');
		}

		foreach($config as $var => $v){
			isset($_CONFIG[$var]) || $_CONFIG[$var] = $v;
		}

		$_G['stylelist'] = array(
			'admin' => array(),
			'user' => array(),
		);
		foreach($_G['stylelist'] as $template_type => &$stylelist){
			$styledir = S_ROOT.'view/'.$template_type.'/';
			$view = opendir($styledir);
			while($style = readdir($view)){
				if($style{0} == '.'){
					continue;
				}

				if(is_dir($styledir.$style)){
					$stylelist[$style] = $style;
				}
			}
		}
		unset($stylelist);

		include view('system');
	}

}

?>
