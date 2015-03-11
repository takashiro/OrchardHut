<?php

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
	'ticket_tips' => $config['ticket_tips'],
	'ticket_extrainfo' => $config['ticket_extrainfo'],
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
