<?php

require_once './core/init.inc.php';

echo Autoreply::Find($_GET['message']) === NULL;

?>
