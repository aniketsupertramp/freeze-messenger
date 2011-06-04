<?php
/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */


$title = 'Logout';


require_once('global.php');


eval(hook('logoutStart'));


setcookie('bbuserId','removed',0,'/','.victoryroad.net');
setcookie('bbpassword','removed',0,'/','.victoryroad.net');
setcookie('bbsessionhash','removed',0,'/','.victoryroad.net');


eval(hook('logoutPostcookie'));


require_once('functions/container.php');
require_once('templateStart.php');


echo container('Thank You','You are now logged out.<br /><br /><a href="/">Return to the main page.</a>');


eval(hook('logoutEnd'));


require_once('templateEnd.php');