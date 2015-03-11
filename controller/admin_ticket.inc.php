<?php

if(!defined('IN_ADMINCP')) exit('access denied');

if($_POST){
	@$config = array(
		'tips' => $_POST['tips'],
		'extrainfo' => $_POST['extrainfo'],
	);

	writedata('ticket', $config);
	showmsg('successfully_updated_system_config', 'refresh');
}

$config = readdata('ticket');
include view('ticket');

?>
