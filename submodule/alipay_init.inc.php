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

$alipay_config = readdata('alipay');
$alipay_config['private_key_path'] = S_ROOT.'data/alipay_private_key_'.$alipay_config['private_key_path'].'.pem';
$alipay_config['ali_public_key_path'] = S_ROOT.'data/alipay_public_key_'.$alipay_config['ali_public_key_path'].'.pem';
$alipay_config['cacert'] = S_ROOT.'data/alipay_cacert.pem';

?>
