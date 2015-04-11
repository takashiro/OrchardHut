<?php

if(!defined('IN_ADMINCP')) exit('access denied');

$condition = array('l.recharged=1');

if(!empty($_REQUEST['time_start'])){
	$time_start = rstrtotime($_REQUEST['time_start']);
	$condition[] = "l.dateline>=$time_start";
}else{
	$time_start = '';
}

if(!empty($_REQUEST['time_end'])){
	$time_end = rstrtotime($_REQUEST['time_end']);
	$condition[] = "l.dateline<=$time_end";
}else{
	$time_end = '';
}

$condition = empty($condition) ? '1' : implode(' AND ', $condition);

$limit = 20;
$offset = ($page - 1) * $limit;
$logs = $db->fetch_all("SELECT l.*, u.nickname
	FROM {$tpre}userwalletlog l
		LEFT JOIN {$tpre}user u ON u.id=l.uid
	WHERE $condition
	ORDER BY l.id DESC
	LIMIT $offset, $limit");

$time_start && $time_start = rdate($time_start);
$time_end && $time_end = rdate($time_end);

$pagenum = $db->result_first("SELECT COUNT(*)
	FROM {$tpre}userwalletlog l
	WHERE $condition");

$stat = array(
	'totaldelta' => $db->result_first("SELECT SUM(l.delta) FROM {$tpre}userwalletlog l WHERE $condition"),
	'totalcost' => $db->result_first("SELECT SUM(l.cost) FROM {$tpre}userwalletlog l WHERE $condition"),
);

include view('userwallet_log');

?>
