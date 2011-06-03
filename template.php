<?php

$noReqLogin = true;
$reqPhrases = true;
$reqHooks = true;

require_once('global.php');

$template = $_GET['template']

switch ($template) {
  case 'kickForm':
  $userid = intval($_POST['userid'] ?: $_GET['userid']);
  $roomid = intval($_POST['roomid'] ?: $_GET['roomid']);

  $roomSelect = mysqlReadThrough(mysqlQuery("SELECT * FROM {$sqlPrefix}rooms WHERE " . ((($user['settings'] & 16) == false) ? "(owner = '$user[userid]' OR moderators REGEXP '({$user[userid]},)|{$user[userid]}$') AND " : '') . "(options & 16) = false AND (options & 4) = false AND (options & 8) = false"),'<option value="$id"{{' . $roomid . ' == $id}}{{ selected="selected"}{}}>$name</option>
');
  $userSelect = mysqlReadThrough(mysqlQuery("SELECT u2.userid, u2.username FROM {$sqlPrefix}users AS u, user AS u2 WHERE u2.userid = u.userid ORDER BY username"),'<option value="$userid"{{' . $userid . ' == $userid}}{{ selected="selected"}{}}>$username</option>
');
  echo template('kickForm');
  break;

  case 'unkickForm':
  case 'copyright':
  case 'userSettingsForm':
  case 'online':
  case 'createRoomForm':
  case 'editRoomForm':
  case 'help':
  echo template($template);
  break;

  default:
  trigger_error("Unknown Template: '$template'", E_USER_ERROR);
  break;
}
?>