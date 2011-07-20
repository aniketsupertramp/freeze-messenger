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

/**
 * Deletes or Undeletes a Message
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <rehtaew@gmail.com>
 * @copyright Joseph T. Parsons 2011
*/

$apiRequest = true;

require_once('../global.php');



/* Get Request Data */
$request = fim_sanitizeGPC(array(
  'post' => array(
    'action' => array(
      'type' => 'string',
      'require' => true,
      'valid' => array(
        'delete',
        'undelete',
        'edit', // Future!
      ),
    ),

    'messageId' => array(
      'type' => 'string',
      'require' => false,
      'default' => 0,
      'context' => array(
        'type' => 'int',
      ),
    ),
  ),
));



/* Data Predefine */
$xmlData = array(
  'editMessage' => array(
    'activeUser' => array(
      'userId' => (int) $user['userId'],
      'userName' => ($user['userName']),
    ),
    'errStr' => ($errStr),
    'errDesc' => ($errDesc),
    'response' => array(),
  ),
);



/* Plugin Hook Start */
($hook = hook('editMessage_start') ? eval($hook) : '');



/* Start Processing */
switch ($request['action']) {
  case 'delete':
  $messageData = $slaveDatabase->getMessage($request['messageId']);
  $roomData = $slaveDatabase->getRoom($messageData['roomId']);

  if (fim_hasPermission($roomData,$user,'editMessage',true)) {
    $database->update(array(
      'deleted' => 1
      ),
      "{$sqlPrefix}messages",
      array(
        "messageId" => $messageId
      )
    );

    $database->update(array(
      'deleted' => 1
      ),
      "{$sqlPrefix}messagesCached",
      array(
        "messageId" => $messageId
      )
    );

    $xmlData['editMessage']['response']['success'] = true;
  }
  else {
    $errStr = 'nopermission';
    $errDesc = 'You are not allowed to moderate this room.';
  }
  break;


  case 'undelete':
  $messageData = $slaveDatabase->getMessage($request['messageId']);
  $roomData = $slaveDatabase->getRoom($messageData['roomId']);

  if (fim_hasPermission($roomData,$user,'editMessage')) {
    $database->update(array(
      'deleted' => 0
      ),
      "{$sqlPrefix}messages",
      array(
        "messageId" => $messageId
      )
    );

    $xmlData['editMessage']['response']['success'] = true;
  }
  else {
    $errStr = 'nopermission';
    $errDesc = 'You are not allowed to moderate this room.';
  }
  break;

  case 'edit':
  //FIMv4
  break;
}



/* Update Data for Errors */
$xmlData['editMessage']['errStr'] = ($errStr);
$xmlData['editMessage']['errDesc'] = ($errDesc);



/* Plugin Hook End */
($hook = hook('editMessage_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);



/* Close Database Conncetion */
dbClose();
?>