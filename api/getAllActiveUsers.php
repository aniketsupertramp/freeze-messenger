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

$apiRequest = true;

require_once('../global.php');
header('Content-type: text/xml');

$time = ($_GET['time'] ?: time());
$onlineThreshold = ($_GET['onlineThreshold'] ?: $onlineThreshold);

$ausers = sqlArr("SELECT
  u.$sqlUserTableCols[username] AS username,
  u.$sqlUserTableCols[userid] AS userid,
  GROUP_CONCAT(r.name) AS roomnames,
  GROUP_CONCAT(r.id) AS roomids
FROM
  $sqlUserTable AS u,
  {$sqlPrefix}rooms AS r,
  {$sqlPrefix}ping AS p
WHERE
  u.$sqlUserTableCols[userid] = p.userid AND
  r.id = p.roomid AND
  UNIX_TIMESTAMP(p.time) > $time - $onlineThreshold
GROUP BY
  p.userid
ORDER BY
  u.username",'userid');

if ($ausers) {
  foreach ($ausers AS $auser) {
    unset($roomsXML);

    $rooms = array_combine(explode(',',$auser['roomids']),explode(',',$auser['roomnames']));
    foreach ($rooms AS $id => $name) $roomsXML .= "      <room>
        <roomid>$id</roomid>
        <roomname>$name</roomname>
      </room>";

    $ausersXML .= "    <user>
      <userdata>
        <userid>$auser[userid]</userid>
        <username>$auser[username]</username>
      </userdata>
      <rooms>
      $roomsXML
      </rooms>
    </user>
";
  }
}

$data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<getAllActiveUsers>
  <activeUser>
    <userId>$user[userid]</userId>
    <userName>" . vrim_encodeXML($user['username']) . "</userName>
  </activeUser>
  <sentData>
    <onlineThreshold>$onlineThreshold</onlineThreshold>
    <time>$time</time>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <users>
    $ausersXML
  </users>
</getAllActiveUsers>";

if ($_GET['gz']) {
 echo gzcompress($data);
}
else {
  echo $data;
}

mysqlClose();
?>