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
 * Edit's a User's Room List
 *
 * @package fim3
 * @version 3.0
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2014
 * TODO -- Optimise
*/

$apiRequest = true;

require('../global.php');


/* Get Request Data */
$request = fim_sanitizeGPC('p', array(
  'roomIds' => array(
    'cast' => 'csv',
    'filter' => 'int',
    'evaltrue' => true,
  ),

  'roomListId' => array(
    'cast' => 'int',
  ),

  'action' => array(
    'valid' => array('add', 'remove', 'replace'),
  ),
));


/* Data Predefine */
$xmlData = array(
  'editIgnoreList' => array(
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
($hook = hook('editIgnoreList_start') ? eval($hook) : '');


switch ($request['action']) {
  case 'add':
  foreach ($request['roomIds'] AS $roomId) {
    if ($roomData = $slaveDatabase->getRoom($roomId)) {
      $database->delete("{$sqlPrefix}roomLists", array(
        'userId' => $user['userId'],
        'roomId' => $roomId,
        'listId' => $request['roomListId'],
      ));

      if (fim_hasPermission($roomData, $user, 'view')) {
        $database->insert("{$sqlPrefix}roomLists", array(
          'userId' => $user['userId'],
          'listId' => $request['roomListId'],
          'roomId' => $roomId,
        ));
      }
      else {
        $errStr = 'noPerm';
        $errDesc = 'You do not have permission to access this room.';
      }
    }
  }
  break;

  case 'remove':
  foreach ($request['roomIds'] AS $roomId) {
    $database->delete("{$sqlPrefix}roomLists", array(
      'userId' => $user['userId'],
      'roomId' => $roomId,
      'listId' => $request['roomListId'],
    ));
  }
  break;

  case 'replace':
  $database->delete("{$sqlPrefix}roomLists", array(
    'userId' => $user['userId'],
    'listId' => $request['roomListId'],
  ));

  foreach ($request['roomIds'] AS $roomId) {
    if ($roomData = $slaveDatabase->getRoom($roomId)) {
      if (fim_hasPermission($roomData, $user, 'view')) {
        $database->insert("{$sqlPrefix}roomLists", array(
          'userId' => $user['userId'],
          'listId' => $request['roomListId'],
          'roomId' => $roomId,
        ));
      }
      else {
        $errStr = 'noPerm';
        $errDesc = 'You do not have permission to access this room.';
      }
    }
  }
  break;
}



/* Plugin Hook Start */
($hook = hook('editIgnoreList_end') ? eval($hook) : '');



/* Output Data */
echo fim_outputApi($xmlData);
?>