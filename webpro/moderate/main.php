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

if (!defined('WEBPRO_INMOD')) {
  die();
}
else {
  echo container('Welcome','<div style="text-align: center; font-size: 40px; font-weight: bold;">Welcome</div><br /><br />

Welcome to the FreezeMessenger control panel. Here you, as one of our well-served grandé and spectacular administrative staff, can perform every task needed to you during normal operation. Still, be careful: you can mess things up here!<br /><br />

To perform an action, click a link on the sidebar. Further instructions can be found in the documentation.<br /><br />
<table class="page ui-widget" border="1">
  <tr>
    <td>System Load Averages</td>
    <td>' . (function_exists('sys_getloadavg') ? print_r(sys_getloadavg(), true) : '--') . '</td>
  </tr>
  <tr>
    <td>Active User</td>
    <td>' . $user['userName'] . '</td>
  </tr>
  <tr>
    <td>FIM Release</td>
    <td>FIMv3.0 ("Bad Wolf")</td>
</table>');
}
?>