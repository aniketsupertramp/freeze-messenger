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




///* Variable Setting *///

$rooms = $_GET['rooms'];
$roomsArray = explode(',',$rooms);
foreach ($roomsArray AS &$v) $v = intval($v);

$newestMessage = intval($_GET['messageIdMax']); // INT
$oldestMessage = intval($_GET['messageIdMin']); // INT

$newestDate = intval($_GET['messageDateMax']); // INT
$oldestDate = intval($_GET['messageDateMin']); // INT

$messageStart = intval($_GET['messageIdStart']); // INT

$watchRooms = intval($_GET['watchRooms']); // BOOL
$activeUsers = intval($_GET['activeUsers']); // BOOL
$archive = intval($_GET['archive']); // BOOL
$encode = ($_GET['encode']); // String - 'base64', 'plaintext'
$fields = ($_GET['fields']); // String - 'api', 'html', or 'both'


$onlineThreshold = intval($_GET['onlineThreshold'] ?: $onlineThreshold); // INT - Only if activeUsers = TRUE

if ($_GET['maxMessages'] == '0') {
  $messageLimit = 500; // Sane maximum.
}
else {
  $messageLimit = ($_GET['maxMessages'] ? intval($_GET['maxMessages']) : ($messageLimit ? $messageLimit : 40));
  if ($messageLimit > 500) $messageLimit = 500; // Sane maximum.
}




///* Query Filter Generation *///

if ($newestMessage) $whereClause .= "AND m.id < $newestMessage ";
if ($oldestMessage) $whereClause .= "AND m.id > $oldestMessage ";
if ($newestdate) $whereClause .= "AND m.date < $newestdate ";
if ($oldestdate) $whereClause .= "AND m.date > $oldestdate ";
if (!$whereClause && $messageStart) {
  echo $whereClause .= "AND m.id > $messageStart AND m.id < " . ($messageStart + $messageLimit);
}




///* Error Checking *///

if (!$rooms) {
  $failCode = 'badroomsrequest';
  $failMessage = 'The room string was not supplied or evaluated to false.';
}
if (!$roomsArray) {
  $failCode = 'badroomsrequest';
  $failMessage = 'The room string was not formatted properly in Comma-Seperated notation.';
}
else {
  foreach ($roomsArray AS $roomXML) {
    $roomsXML .= "<room>$roomXML</room>";
  }

  foreach ($roomsArray AS $room2) {
    $room2 = intval($room2);
    $room = sqlArr("SELECT * FROM {$sqlPrefix}rooms WHERE id = $room2");

    if ($room) {
      if (!hasPermission($room,$user)) { } // Gotta make sure the user can view that room.
      else {

        if (!$_GET['noping']) {
          mysqlQuery("INSERT INTO {$sqlPrefix}ping (userid,roomid,time) VALUES ($user[userid],$room[id],CURRENT_TIMESTAMP()) ON DUPLICATE KEY UPDATE time = CURRENT_TIMESTAMP()");
        }

        switch ($_GET['fields']) {
          case 'both': $messageFields = 'm.apiText AS apiText, m.htmlText AS htmlText,'; break;
          case 'api': $messageFields = 'm.apiText AS apiText,'; break;
          case 'html': $messageFields = 'm.htmlText AS htmlText,'; break;
          default:
            $failCode = 'badFields';
            $failMessage = 'The given message fields are invalid - recognized values are "api", "html", and "both"';
          break;
        }

        if ($archive) {

          $messages = sqlArr("SELECT m.id,
  UNIX_TIMESTAMP(m.time) AS time,
  $messageFields
  m.iv AS iv,
  m.salt AS salt,
  u.{$sqlUserIdCol} AS userid,
  u.{$sqlUsernameCol} AS username,
  u.{$sqlUsergroupCol} AS displaygroupid,
  u2.defaultColour AS defaultColour,
  u2.defaultFontface AS defaultFontface,
  u2.defaultHighlight AS defaultHighlight,
  u2.defaultFormatting AS defaultFormatting
FROM {$sqlPrefix}messages AS m,
  user AS u,
  {$sqlPrefix}users AS u2
WHERE room = $room[id]
  AND m.deleted != true
  AND m.user = u.userid
  AND m.user = u2.userid
$whereClause
ORDER BY m.id DESC
LIMIT $messageLimit",'id');

        }
        else {

          $messages = sqlArr("SELECT m.messageid AS id,
  UNIX_TIMESTAMP(m.time) AS time,
  $messageFields
  m.userid AS userid,
  m.username AS username,
  m.usergroup AS displaygroupid,
  m.flag AS flag,
  u2.settings AS usersettings,
  u2.defaultColour AS defaultColour,
  u2.defaultFontface AS defaultFontface 
  u2.defaultHighlight AS defaultHighlight,
  u2.defaultFormatting AS defaultFormatting
FROM {$sqlPrefix}messagesCached AS m,
  {$sqlPrefix}users AS u2
WHERE m.roomid = $room[id]
  AND m.userid = u2.userid
$whereClause
ORDER BY m.id DESC
LIMIT $messageLimit",'id');

        }

        if ($messages) {
          if ($_GET['order'] == 'reverse') $messages = array_reverse($messages);
          foreach ($messages AS $id => $message) {
            $message = vrim_decrypt($message);

            $message['username'] = addslashes($message['username']);
            $message['apiText'] = vrim_encodeXML($message['apiText']);
            $message['htmlText'] = vrim_encodeXML($message['htmlText']);

            switch ($encode) {
              case 'base64':
              $message['apiText'] = base64_encode($message['apiText']);
              $message['htmlText'] = base64_encode($message['htmlText']);
              break;
            }

            $message['displaygroupid'] = displayGroupToColour($message['displaygroupid']);
            $messageXML .=  "    <message>
      <roomdata>
        <roomid>$room[id]</roomid>
        <roomname>$room[name]</roomname>
        <roomtopic>$room[title]</roomtopic>
      </roomdata>
      <messagedata>
        <messageid>$message[id]</messageid>
        <messagetime>$message[time]</messagetime>
        <messagetext>
          <apptext>$message[apiText]</apptext>
          <htmltext>$message[htmlText]</htmltext>
        </messagetext>
        <flags>$message[flag]</flags>
      </messagedata>
      <userdata>
        <username>$message[username]</username>
        <userid>$message[userid]</userid>
        <displaygroupid>$message[displaygroupid]</displaygroupid>
        <defaultFormatting>
          <color>$message[defaultColour]</color>
          <highlight>$message[defaultHighlight]</highlight>
          <fontface>$message[defaultFontface]</fontface>
          <general>$message[defaultFormatting]</general>
        </defaultFormatting>
      </userdata>
    </message>";
          }
        }

        ///* Process Active Users
        if ($activeUsers) {
  $ausers = sqlArr("SELECT u.{$sqlUsernameCol} AS username,
  u.{$sqlUseridCol} AS userid,
  u.{$sqlUsergroupCol} AS displaygroupid,
  p.status,
  p.typing
FROM {$sqlPrefix}ping AS p
  {$sqlUserTable} AS u,
WHERE p.roomid IN  $room[id]
  AND p.userid = u.userid
  AND UNIX_TIMESTAMP(p.time) >= (UNIX_TIMESTAMP(NOW()) - $onlineThreshold)
ORDER BY u.username
LIMIT 500",'userid');

  if ($ausers) {
    foreach ($ausers AS $user) {
        $ausersXML .= "      <user>
        <username>$message[username]</username>
        <userid>$message[userid]</userid>
        <displaygroupid>$message[displaygroupid]</displaygroupid>
        <displaygroupcolor>" . displayGroupToColour($message['displaygroupid']) . "</displaygroupcolor>
      </user>
";
      }
    }
}
      }
    }
  }
}



///* Process Watch Rooms *///
if ($watchRooms) {
  /* Get Missed Messages */
  $missedMessages = sqlArr("SELECT r.*, UNIX_TIMESTAMP(r.lastMessageTime) AS lastMessageTimestamp FROM {$sqlPrefix}rooms AS r LEFT JOIN {$sqlPrefix}ping AS p ON (p.userid = $user[userid] AND p.roomid = r.id) WHERE (r.options & 16 " . ($user['watchRooms'] ? " OR r.id IN ($user[watchRooms])" : '') . ") AND (r.allowedUsers REGEXP '({$user[userid]},)|{$user[userid]}$' OR r.allowedUsers = '*') AND IF(p.time, UNIX_TIMESTAMP(r.lastMessageTime) > (UNIX_TIMESTAMP(p.time) + 10), TRUE)",'id'); // Right now only private IMs are included, but in the future this will be expanded.

  if ($missedMessages) {
    foreach ($missedMessages AS $message) {
      if (!hasPermission($message,$user,'view')) { continue; }

      $roomName = vrim_encodeXML($message['name']);
      $watchRoomsXML .= "    <room>
      <roomid>$message[id]</roomid>
      <roomname>$roomName</roomname>
      <lastMessageTime>$message[lastMessageTimestamp]</lastMessageTime>
    </room>";
    }
  }
}




///* Output *///
$data = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
<!DOCTYPE html [
  <!ENTITY nbsp \" \">
]>
<getMessages>
  <activeUser>
    <userid>$user[userid]</userid>
    <username>" . vrim_encodeXML($user['username']) . "</username>
  </activeUser>
  <sentData>
    <rooms>$rooms</rooms>
    <roomsList>
    $roomsXML
    </roomsList>
    <newestMessage>$newestMessage</newestMessage>
    <oldestMessage>$oldestMessage</oldestMessage>
    <newestDate>$newestDate</newestDate>
    <oldestDate>$oldestDate</oldestDate>
  </sentData>
  <errorcode>$failCode</errorcode>
  <errortext>$failMessage</errortext>
  <messages>
    $messageXML
  </messages>
  <watchrooms>
    $watchRoomsXML
  </watchrooms>
  <activeUsers>
    $ausersXML
  </activeUsers>
</getMessages>";


if ($_GET['gz']) {
 echo gzcompress($data);
}
else {
  echo $data;
}

mysqlClose();
?>