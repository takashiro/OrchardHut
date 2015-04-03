<?php

/********************************************************************
 Copyright (c) 2013-2015 - Kazuichi Takashiro

 This file is part of Orchard Hut.

 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.

 takashiro@qq.com
*********************************************************************/

if(!defined('IN_ADMINCP')) exit('access denied');

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
	'head_element' => htmlspecialchars_decode(stripslashes($config['head_element'])),
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

?>
