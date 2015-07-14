
/***********************************************************************
Orchard Hut Online Shop
Copyright (C) 2013-2015  Kazuichi Takashiro

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

takashiro@qq.com
************************************************************************/

$(function(){
	$('.dropmenu').each(function(){
		var menu = $(this);
		var submenu = menu.children('.submenu');

		submenu.css({
			top : -submenu.outerHeight(),
			left : menu.offset().left
		});
	});

	$('.dropmenu').click(function(){
		var submenu = $(this).children('.submenu');
		submenu.fadeToggle();
	});
});
