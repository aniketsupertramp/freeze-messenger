<?php
/* FreezeMessenger Copyright © 2014 Joseph Todd Parsons

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

/**
 * Get Messages from the Server
 * Works with both private and normal rooms.
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 *
 * @param int roomId - The room ID.
 * @param string message - The message text, properly URLencoded.
 * @param string flag - A message content-type/context flag, used for sending images, urls, etc.
 * @param bool ignoreBlock - If true, the system will ignore censor warnings. You must pass this to resend a message that was denied because of a censor warning.
*/

$apiRequest = true;

require('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'roomId' => array(
    'require' => true,
  ),

  'message' => array(),

  'flag' => array(
    'valid' => array('image', 'video', 'url', 'email', 'html', 'audio', 'text', 'source', ''),
  ),

  'ignoreBlock' => array(
    'default' => false,
    'cast' => 'bool',
  ),
));
$ip = $_SERVER['REMOTE_ADDR']; // Get the IP address of the user.



/* Get Room for DB */
$room = $database->getRoom($request['roomId']);


/* Censor Fun */
$blockedWord = false;
$blockedWordText = false;
$blockedWordReason = false;
$blockedWordSeverity = false;
$blockWordApi = array(
  'word' => '',
  'severity' => '',
  'reason' => '',
);

if ($censorWordsCache['byWord']) {
  foreach ($censorWordsCache['byWord'] AS $word) {
    if ($request['ignoreBlock'] && $word['severity'] === 'confirm') continue;

    $searchText[] = addcslashes(strtolower($word['word']), '^&|!$?()[]<>\\/.+*');
  }


  if ($searchText) {
    preg_match('/(' . implode('|',$searchText) . ')/i', $request['message'], $matches);
    
    if ($matches[1]) {
      $blockedWord = strtolower($matches[1]);
      $blockedWordText = $censorWordsCache['byWord'][$blockedWord]['word'];
      $blockedWordReason = $censorWordsCache['byWord'][$blockedWord]['param'];
      $blockedWordSeverity = $censorWordsCache['byWord'][$blockedWord]['severity'];
    }
  }
}



/* Start Processing */
if (!$room->id) new fimError('badRoom'); // Room doesn't exist.
elseif (strlen($request['message']) < $config['messageMinLength'] || strlen($request['message']) > $config['messageMaxLength']) new fimError('messageLength'); // Too short/long.
elseif (preg_match('/^(\ |\n|\r)*$/', $request['message'])) new fimError('spaceMessage'); // All spaces. TODO: MB Support
elseif (!($database->hasPermission($user, $room) & ROOM_PERMISSION_POST)) new fimError('noPerm');
elseif (in_array($request['flag'], array('image', 'video', 'url', 'html', 'audio'))
  && !filter_var($request['message'], FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) new fimError('badUrl'); // If the message is supposed to be a URI, make sure it is. (We do this here and not at the function level to allow for plugins to override such a check).
elseif ($request['flag'] === 'email'
  && !filter_var($request['message'], FILTER_VALIDATE_EMAIL)) new fimError('badUrl'); // If the message is suppoed to be an email, make sure it is. (We do this here and not at the function level to allow for plugins to override such a check).
elseif ($blockedWordSeverity === 'block') new fimError('blockCensor', 'The message can not be sent because a word is not allowed.', array('word' => $blockedWordText, 'reason' => $blockedWordReason));
elseif ($blockedWordSeverity === 'confirm') new fimError('confirmCensor', 'The message must be resent because a word may not be allowed.', array('word' => $blockedWordText, 'reason' => $blockedWordReason));
elseif (strpos($request['message'], '/kick') === 0) { // TODO
  $kickData = preg_replace('/^\/kick (.+?)(| ([0-9]+?))$/i','$1,$2',$request['message']);
  $kickData = explode(',',$kickData);

  $userData = $database->getUsers(array(
    'userNames' => array($kickData[0])
  ))->getAsUser();

  $userData->kick($kickData[1]);
}
else {
  if (strpos($request['message'], '/topic') === 0 && ($database->hasPermission($user, $room) & ROOM_PERMISSION_TOPIC)) {
    $room->changeTopic(preg_replace('/^\/topic( |)(.+?)$/i', '$2', $request['message']));
  }

  $database->storeMessage($request['message'], $request['flag'], $user, $room);
}





/* Data Define */
$xmlData = array(
  'sendMessage' => array(
    'censor' => array(
      'word' => $blockedWordText,
      'reason' => $blockedWordReason,
    ),
  ),
);



/* Output Data */
new apiData($xmlData, true);
?>