<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$actions = array('logout', 'edit');
$action = !empty($_GET['action']) && in_array($_GET['action'], $actions) ? $_GET['action'] : $actions[0];

if($action == 'logout'){
	$_G['admin']->logout();
	redirect('admin.php');
}


?>
