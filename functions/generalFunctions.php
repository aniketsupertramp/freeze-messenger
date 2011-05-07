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

function inArray($needle,$haystack) {
  foreach($needle AS $need) {
    if (in_array($need,$haystack)) {
      return true;
    }
  }
  return false;
}

function hasPermission($roomData,$userData,$type = 'post') { // The below permissions are very hierachle.
  global $sqlPrefix, $banned;

  if (!$roomData['id']) {
    return false;
  }

  if ($userData['userid']) {
    $kick = sqlArr("SELECT * FROM {$sqlPrefix}kick WHERE userid = $userData[userid] AND room = $roomData[id] AND UNIX_TIMESTAMP(NOW()) <= (UNIX_TIMESTAMP(time) + length)");
  }

  switch ($type) {
    case 'post':
    if ($banned) $roomValid = false; // The user is banned.
    elseif (($userData['settings'] & 16) && (($roomData['options'] & 16) == false)) $roomValid = true; // The user is an admin.
    elseif ($roomData['options'] & 4) $roomValid = false; // The room is deleted.
    elseif ($roomData['owner'] == $userData['userid'] && $roomData['owner'] > 0)  $roomValid = true; // The users owns the room (and it is not deleted).
    elseif (in_array($userData['userid'],explode(',',$roomData['moderators'])) && $roomData['moderators']) $roomValid = true; // The user is one of the chat moderators (and it is not deleted).
    elseif ($kick['id']) $roomValid = false;
    elseif ((in_array($userData['userid'],explode(',',$roomData['allowedUsers'])) || $roomData['allowedUsers'] == '*') && $roomData['allowedUsers']) $roomValid = true; // The user is in the allowed users column (and it is not deleted).
    elseif ((inArray(explode(',',$userData['membergroupids']),explode(',',$roomData['allowedGroups'])) || $roomData['allowedGroups'] == '*') && $roomData['allowedGroups']) $roomValid = true; // The user is a part of a group that is in the allowed groups (and it is not deleted).
    else $roomValid = false; // The user is not allowed either via being an owner, moderator (for the chat itself or the forums),
    break;

    case 'view':
    if (($userData['settings'] & 16) && (($roomData['options'] & 16) == false)) $roomValid = true; // The user is an admin.
    elseif ($roomData['owner'] == $userData['userid'] && $roomData['owner'] > 0) $roomValid = true; // The users owns the room.
    elseif ($roomData['options'] & 4) $roomValid = false; // The room is deleted.
    elseif ((in_array($userData['userid'],explode(',',$roomData['moderators']))) && $roomData['moderators']) $roomValid = true; // The user is one of the chat moderators (and it is not deleted).
    //elseif ($kick['id']) $roomValid = false;
    elseif ((in_array($userData['userid'],explode(',',$roomData['allowedUsers'])) || $roomData['allowedUsers'] == '*') && $roomData['allowedUsers']) $roomValid = true; // The user is in the allowed users column (and it is not deleted).
    elseif ((inArray(explode(',',$userData['membergroupids']),explode(',',$roomData['allowedGroups'])) || $roomData['allowedGroups'] == '*') && $roomData['allowedGroups']) $roomValid = true; // The user is a part of a group that is in the allowed groups (and it is not deleted).
    else $roomValid = false; // The user is not allowed either via being an owner, moderator (for the chat itself or the forums),
    break;

    case 'moderate':
    if ($banned) $roomValid = false; // The user is banned.
    elseif (($userData['settings'] & 16) && (($roomData['options'] & 16) == false)) $roomValid = true; // The user is an admin.
    elseif ($roomData['owner'] == $userData['userid'] && $roomData['owner'] > 0)  $roomValid = true; // The users owns the room (and it is not deleted).
    elseif (in_array($userData['userid'],explode(',',$roomData['moderators'])) && $roomData['moderators']) $roomValid = true; // The user is one of the chat moderators (and it is not deleted).
    else $roomValid = false; // The user is not allowed either via being an owner, moderator (for the chat itself or the forums),
    break;

    case 'admin':
    if ($banned) $roomValid = false; // The user is banned.
    elseif (($userData['settings'] & 16) && (($roomData['options'] & 16) == false)) $roomValid = true; // The user is an admin.
    elseif ($roomData['owner'] == $userData['userid'] && $roomData['owner'] > 0) $roomValid = true; // The user owns the room (and it is not deleted).
    else $roomValid = false; // The user is not allowed either via being an owner, moderator (for the chat itself or the forums),
    break;

    case 'know':
    if ($userData['settings'] & 16) $roomValid = true; // The user is an admin.
    elseif ($roomData['owner'] == $userData['userid'] && $roomData['owner'] > 0)  $roomValid = true; // The users owns the room.
    elseif ((in_array($userData['userid'],explode(',',$roomData['moderators']))) && $roomData['moderators']) $roomValid = true; // The user is one of the chat moderators.
    elseif ((in_array($userData['userid'],explode(',',$roomData['allowedUsers'])) || $roomData['allowedUsers'] == '*') && $roomData['allowedUsers']) $roomValid = true; // The user is in the allowed users column (and it is not deleted).
    elseif ((inArray(explode(',',$userData['membergroupids']),explode(',',$roomData['allowedGroups'])) || $roomData['allowedGroups'] == '*') && $roomData['allowedGroups']) $roomValid = true; // The user is a part of a group that is in the allowed groups (and it is not deleted).
    else $roomValid = false; // The user is not allowed either via being an owner, moderator (for the chat itself or the forums),
    break;
  }

  return $roomValid;
}

function displayGroupToColour($id) {
  switch ($id) {
    case 5: return '0,170,0'; break;
    case 6: return '170,0,0'; break;
    case 7: return '0,0,255'; break;
    case 23: return '255,140,0'; break;
    case 24: return '255,140,0'; break;
    case 25: return '0,127,127'; break;
    case 30: return '255,0,0'; break;
    case 36: return '170,0,170'; break;
  }
}

function userFormat($message, $room, $messageTable = true) {
  $colour = 'color: rgb(' . displayGroupToColour($message['displaygroupid']) . '); ';
  $class = ($messageTable ? 'username usernameTable' : 'username');
  if (in_array($message['userid'],explode(',',$room['moderators'])) || $message['usersettings'] & 16 || $message['userid'] == $room['owner']) $userAppend = '*';

  return "<span style=\"{$colour}\" class=\"{$class}\" data-userid=\"$message[userid]\">$message[username]{$userAppend}</span>";
}

function messageStyle($message) {
  global $enableDF, $user;

  if ($enableDF && (($user['settings'] & 512) == false) && !in_array($message['flag'],array('me','topic','kick'))) {
    if ($message['defaultColour'] && $enableDF['colour']) $style .= "color: rgb($message[defaultColour]); ";
    if ($message['defaultFontface'] && $enableDF['font']) $style .= "font-family: $message[defaultFontface]; ";
    if ($message['defaultHighlight'] && $enableDF['highlight']) $style .= "background-color: rgb($message[defaultHighlight]); ";
    if ($message['defaultFormatting'] && $enableDF['general']) {
      $df = $message['defaultFormatting'];

      if ($df & 256) $style .= "font-weight: bold; ";
      if ($df & 512) $style .= "font-style: italic; ";
    }
  }

  return $style;
}

function vrim_urldecode($str) {
  return str_replace(array('%2b','%26'),array('+','&'),$str);
}

function vrim_decrypt($message,$index = false) {
  global $salts;

  if ($message['salt'] && $message['iv']) {
    $salt = $salts[$message['salt']];

    if ($index) {
      if (is_array($index)) {
        foreach ($index AS $index2) {
          $message[$index2] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message[$index2]), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
        }
      }
      else {
        $message[$index] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message[$index]), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
      }
    }
    else {
      if ($message['apiText']) $message['apiText'] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message['apiText']), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
      if ($message['htmlText']) $message['htmlText'] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message['htmlText']), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
      if ($message['rawText']) $message['rawText'] = rtrim(mcrypt_decrypt(MCRYPT_3DES, $salt, base64_decode($message['rawText']), MCRYPT_MODE_CBC,base64_decode($message['iv'])),"\0");
    }
  }

  return $message;
}

function vrim_encrypt($data) {
  global $salts;

  $salt = end($salts);
  $saltNum = key($salts);

  $iv_size = mcrypt_get_iv_size(MCRYPT_3DES,MCRYPT_MODE_CBC);
  $iv = base64_encode(mcrypt_create_iv($iv_size, MCRYPT_RAND));

  if (is_array($data)) {
    foreach ($data AS &$data2) {
      $data2 = base64_encode(rtrim(mcrypt_encrypt(MCRYPT_3DES, $salt, $data2, MCRYPT_MODE_CBC, base64_decode($iv)),"\0"));
    }
  }
  else {
    $data = base64_encode(rtrim(mcrypt_encrypt(MCRYPT_3DES, $salt, $data, MCRYPT_MODE_CBC, base64_decode($iv)),"\0"));
  }

  return array($data,$iv,$saltNum);
}

function vrim_encodeXML($data) {
  $ref = array(
    '&' => '&amp;', // By placing this first, we avoid accidents!
    '\'' => '&apos;',
    '<' => '&lt;',
    '>' => '&gt;',
    '"' => '&quot;',
  );

  foreach ($ref AS $search => $replace) {
    $data = str_replace($search,$replace,$data);
  }

  return $data;
}

function html2rgb($color) {
  if ($color[0] == '#') {
    $color = substr($color, 1);
  }

  if (strlen($color) == 6) { echo 1;
    list($r, $g, $b) = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
  }
  elseif (strlen($color) == 3) { echo 2;
    list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
  }
  else { echo 3;
    return false;
  }

  $r = hexdec($r);
  $g = hexdec($g);
  $b = hexdec($b);

  return array($r, $g, $b);
}

function rgb2html($r, $g = false, $b = false) {
  if (is_array($r) && sizeof($r) == 3) list($r, $g, $b) = $r;

  $r = intval($r);
  $g = intval($g);
  $b = intval($b);

  $r = dechex($r < 0 ? 0 : ($r > 255 ? 255 : $r));
  $g = dechex($g < 0 ? 0 : ($g > 255 ? 255 : $g));
  $b = dechex($b < 0 ? 0 : ($b > 255 ? 255 : $b));

  $color = (strlen($r) < 2 ? '0' : '') . $r;
  $color .= (strlen($g) < 2 ? '0' : '') . $g;
  $color .= (strlen($b) < 2 ? '0' : '') . $b;

  return '#' . $color;
}

function vbdate($format,$timestamp = false) {
  global $user;
  $timestamp = ($timestamp ?: time());

  $hourdiff = (date('Z', $timestamp) / 3600 - $user['timezoneoffset']) * 3600;

  $timestamp_adjusted = $timestamp - $hourdiff;

  if ($format == false) { // Used for most messages
    $midnight = strtotime("yesterday") - $hourdiff;
    if ($timestamp_adjusted > $midnight) $format = 'g:i:sa';
    else $format = 'm/d/y g:i:sa';
  }

  $returndate = date($format, $timestamp_adjusted);

  return $returndate;
}

function button($text,$url,$postVars = false) {
  return '<form method="post" action="' . $url . '" style="display: inline;">
  <button type="submit">' . $text . '</button>
</form>';
}

function hook($name) {
  global $hooks;
  $hook = $hooks[$name];

  if ($hook) {
    return $hook;
  }
  else {
    return 'return false;';
  }
}

function template($name) {
  global $templates, $phrases, $title, $user, $room, $message, $template, $templateVars; // Lame approach.

  if($templateVars[$name]) {
    $vars = explode(',',$templateVars[$name]);
    foreach ($vars AS $var) {
      $globalVars[] = '$' . $var;
    }
    $globalString = implode(',',$globalVars);

    eval("global $globalString;");
  }

  $template2 = $templates[$name];


  $template2 = preg_replace('/<if cond="(.+?)">(.+?)(<else \/>(.+?)|)<\/if>/es',"iifl('\\1','\\2','\\4','global $globalString;')",$template2);
//  $template2 = preg_replace('/<if cond="(.+?)">(.+?)<\/if>/es',"stripslashes(iifl('\\1','\\2','','global $globalString;'))",$template2);
  $template2 = preg_replace('/(.+)/e','stripslashes("\\1")',$template2);
  return $template2;
}

function iifl($condition,$true,$false,$eval) {
  global $templates, $phrases, $title, $user, $room, $message, $template, $templateVars; // Lame approach.

  if($eval) {
    eval($eval);
  }

  if (eval('return ' . stripslashes($condition) . ';')) {
    return stripslashes($true);
  }
  return stripslashes($false);
}

function errorHandler($errno, $errstr, $errfile, $errline) {
  global $lite;

  $errorString = $errstr . ($_GET['showErrorsFull'] ? " on line $errline" : '');

  if ($lite && function_exists('container')) {
    switch ($errno) {
      case E_USER_ERROR:
      echo container('Error',$errorString);
      break;
      case E_USER_WARNING:
      echo container('Error [Ignored]',$errorString);
      break;
      case E_USER_NOTICE:
      break;
      case E_ERROR:
      echo container('System Error',$errorString);
      break;
      case E_WARNING:
      echo container('System Error [Ignored]',$errorString);
      break;
      case E_NOTICE:
      break;
      default:
      echo container('Invalid error code: the error handler could not launch.');
      break;
    }
  }
  else {
    switch ($errno) {
      case E_USER_ERROR:
      echo '<div class="ui-state-error">' . $errorString . '</div>';
      break;
      case E_USER_WARNING:
      echo '<div class="ui-state-error">The following error has been encountered, though it has been ignored: "' . $errorString . '".</div>';
      break;
      case E_USER_NOTICE:
      break;
      case E_ERROR:
      die('The script you are running has died with the error "' . $errorString . '".<br />');
      break;
      case E_WARNING:
      echo '<div class="ui-state-error">System error: "' . $errorString . '".</div>';
      break;
      case E_NOTICE:
      break;
      default:
      echo '<div class="ui-state-error">Invalid error code: the error handler could not launch.</div>';
      break;
    }
  }

  error_log("$errno-level error in $errfile on line $errline: $errstr");

  // Don't execute the internal PHP error handler.
  return true;
}
?>