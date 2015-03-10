<?php

require_once './core/init.inc.php';

$limit = 10;
$offset = ($page - 1) * $limit;
$db->select_table('userwalletlog');
$pagenum = $db->RESULTF('COUNT(*)');
$walletlog = $db->MFETCH('*', "1 ORDER BY dateline LIMIT $offset,$limit");

include view('wallet');

?>
