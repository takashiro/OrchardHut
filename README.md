Orchard Hut Shop Online
==========

| Example Page | http://weifruit.cn   |
|--------------|----------------------|
| Author       | Kazuichi Takashiro   |


Lisense
-------
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

Introduction
------------

Orchard Hut is a mini e-shop system (embeded in WeChat, optional).

Features
----------

1. Basic Functions
    * Error Reporter, record each run-time error as a report in data/error/
    * File-based data storage and cache
    * Simple but powerful template engine with statements like if, elseif, else, loop, echo and eval.
    * Frequently-used input elements like select, radio and checkbox can be output easily without foreach or many if statments
    * MySQL connection class
    * Automatically attached and removed cookie prefix to avoid cookie collision
    * Hook engine, for open-close principle
    * Timed announcements
    *

2. Product management
    * Multiple prices for one product
    * Storage management
    * Timed price

3. Online Payment
    * Alipay via mobile webpage supported


Running Environment
-------------------
1. PHP 5.5
2. MySQL 5
3. Apache Or IIS
4. PHP extension: cURL, openssl

Run http://yoursitename/install and it will be working.
