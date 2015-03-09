<?php

$alipay_config = readdata('alipay');
$alipay_config['private_key_path'] = S_ROOT.'data/alipay_private_key_'.$alipay_config['private_key_path'].'.pem';
$alipay_config['ali_public_key_path'] = S_ROOT.'data/alipay_public_key_'.$alipay_config['ali_public_key_path'].'.pem';
$alipay_config['cacert'] = S_ROOT.'data/alipay_cacert.pem';

?>
