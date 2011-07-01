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


/* Quick Notes:
 * Magic Session Hash (Cookie Store): hash('sha256',hash('sha256',uniqid('',true)) . salt)
 * Magic Session Hash (DB Check): hash('sha256',hash('sha256',hash('sha256',uniqid('',true)) . salt) . userId)
 * Password Hash: sha1(sha1(password) . user_iv) */

/* Useful Links:
 **** Session Security ****
 * http://phpsec.org/projects/guide/4.html
 * http://www.devshed.com/c/a/PHP/Creating-a-Secure-PHP-Login-Script/
 * */


///* Require Base *///

require_once('global.php');







///* Some Pre-Stuff *///

require_once('functions/loginReqs.php');


static $apiVersion, $goodVersion, $sqlUserTable, $sqlUserGroupTable, $sqlMemberGroupTable, $sqlSessionTable, $sqlUserTableCols, $sqlUserGroupTableCols, $sqlMemberGroupTablecols, $api, $userName, $userId, $password, $sessionHssh;

if (!isset($cookiePrefix)) {
  $cookiePrefix = 'fim3_';
}






///* Obtain Login Data From Different Locations *///

if (isset($_POST['userName'],$_POST['password'])) { // API.
  $apiVersion = $_POST['apiVersion']; // Get the version of the software the client intended for.

  if (!$apiVersion) {
    define('LOGIN_FLAG','API_VERSION_STRING');
  }

  else {
    $apiVersionList = explode(',',$_POST['apiVersion']); // Split for all acceptable versions of the API.

    foreach ($apiVersionList AS $version) {
      $apiVersionSubs = explode('.',$_POST['apiVersion']); // Break it up into subversions.
      if ($apiVersionSubs[0] == 3 && $apiVersionSubs[1] == 0 && $apiVersionSubs[2] == 0) { // This is the same as version 3.0.0.
        $goodVersion = true;
      }
    }

    if ($goodVersion) {
      $userName = fim_urldecode($_POST['userName']);
      $password = fim_urldecode($_POST['password']);
      $passwordEncrypt = fim_urldecode($_POST['passwordEncrypt']);

      switch ($passwordEncrypt) {
        case 'md5':
        case 'plaintext':
        // Do nothing, yet.
        break;

        case 'base64':
        $password = base64_decode($password);
        break;

        default:
        define('LOGIN_FLAG','PASSWORD_ENCRYPT');
        break;
      }
    }
  }

  $api = true;
}

elseif (isset($_GET['fim3_sessionHash'])) { // Session hash defined via sent data.
  $sessionHash = $_GET['fim3_sessionHash'];

  $userIdComp = $_GET['fim3_userId'];
}

elseif (isset($_POST['fim3_sessionHash'])) { // Session hash defined via sent data.
  $sessionHash = $_POST['fim3_sessionHash'];

  $userIdComp = $_POST['fim3_userId'];

  if (isset($_POST['apiLogin'])) {
    $api = true;
  }
}

elseif ((int) $anonymousUser >= 1 && isset($_POST['apiLogin'])) { // Unregistered user support.
  $userId = $anonymousUser;
  $anonymous = true;
  $api = true;
}


($hook = hook('validate_retrieval') ? eval($hook) : '');






///* Required Forum-Included Functions *///

/* Set Relevant Column Data */
switch ($loginConfig['method']) {

  case 'vbulletin':
  $sqlUserTable = $forumTablePrefix . 'user'; // The user table in the login method used.
  $sqlAdminGroupTable = $forumTablePrefix . 'usergroup'; // The adminGroup table in the login method used.
  $sqlUserGroupTable = $forumTablePrefix . 'socialgroup';
  $sqlMemberGroupTable = $forumTablePrefix . 'socialgroupmember'; // The userGroup table in the login method used.
  $sqlSessionTable = $forumTablePrefix . 'session'; // The sessions table in the login method used.

  $sqlUserTableCols = array(
    'userId' => 'userid', // The user ID column of the user table in the login method used.
    'userName' => 'username', // The userName column of the user table in the login method used.
    'userGroup' => 'displaygroupid', // The userGroup column of the user table in the login method used.
    'allGroups' => 'membergroupids', // All admin-defined groups the user is a part of.
    'timeZone' => 'timezoneoffset', // Timezone offset
    'options' => 'options', // Options bitfield for some rare uses.
  );
  $sqlAdminGroupTableCols = array(
    'groupId' => 'usergroupid', // Group ID
    'groupName' => 'title', // Group Name
    'startTag' => 'opentag',
    'endTag' => 'closetag',
  );
  $sqlUserGroupTableCols = array(
    'groupId' => 'groupid', // Group ID
    'groupName' => 'name', // Group Name
  );
  $sqlMemberGroupTableCols = array(
    'groupId' => 'groupid', // Social Group ID
    'userId' => 'userid', // Social Group Member ID
    'type' => 'type', // Social Group Type
    'validType' => 'member', // Valid Social Group Type
  );
  break;

  case 'phpbb':
  $sqlUserTable = $forumTablePrefix . 'users'; // The user table in the login method used.
  $sqlUserGroupTable = $forumTablePrefix . 'groups'; // The userGroup table in the login method used.
  $sqlMemberGroupTable = $forumTablePrefix . 'user_group'; // The userGroup table in the login method used.
  $sqlSessionTable = $forumTablePrefix . 'sessions'; // The sessions table in the login method used.

  $sqlUserTableCols = array(
    'userId' => 'user_id', // The user ID column of the user table in the login method used.
    'userName' => 'username', // The userName column of the user table in the login method used.
    'userGroup' => 'group_id', // The userGroup column of the user table in the login method used.
    'allGroups' => 'group_id',
    'timeZone' => 'user_timezone',
    'color' => 'user_colour',
    'avatar' => 'user_avatar',
  );
  $sqlAdminGroupTableCols = array(
  );
  $sqlUserGroupTableCols = array(
    'groupId' => 'group_id',
    'groupName' => 'group_name',
  );
  $sqlMemberGroupTableCols = array(
    'groupId' => 'group_id',
    'userId' => 'user_id',
    'type' => 'user_pending',
    'validType' => '0',
  );
  break;

  case 'vanilla':
  $sqlUserTable = $tablePrefix . 'users'; // The user table in the login method used.
  $sqlUserGroupTable = $tablePrefix . 'groups'; // The userGroup table in the login method used.
  $sqlSessionTable = $tablePrefix . 'sessions'; // The sessions table in the login method used.

  $sqlUserTableCols = array(
    'userId' => 'userId', // The user ID column of the user table in the login method used.
    'userName' => 'userName', // The userName column of the user table in the login method used.
    'userGroup' => 'userGroup', // The userGroup column of the user table in the login method used.
    'allGroups' => 'allGroups',
    'timeZone' => 'timeZone',
    'avatar' => 'avatar',
  );
  $sqlUserGroupTableCols = array(
    'groupId' => 'groupId',
    'groupName' => 'groupName',
  );
  $sqlMemberGroupTableCols = array(
    'groupId' => 'group_id',
    'userId' => 'user_id',
    'type' => 'user_pending',
    'validType' => '0',
  );
  break;

  default:
  trigger_error("Login method '$loginConfig[method]' unrecognized.",E_USER_ERROR);
  break;

}

($hook = hook('validate_start') ? eval($hook) : '');






///* Process Login Data *///

if ($flag) {
  // Do nothing.
}
else {
  if ($sessionHash) {

//    $user = dbRows("SELECT u.*, s.anonId, UNIX_TIMESTAMP(s.time) AS sessionTime, s.ip AS sessionIp, s.browser AS sessionBrowser FROM {$sqlPrefix}sessions AS s, {$sqlPrefix}users AS u WHERE s.magicHash = '" . dbEscape($sessionHash) . "' AND u.userId = s.userId");

    $user = $database->select(
      array( // Columns
        "{$sqlPrefix}users" => array(
          'userId' => 'userId',
          'userName' => 'userName',
          'userGroup' => 'userGroup',
          'allGroups' => 'allGroups',
          'avatar' => 'avatar',
          'profile' => 'profile',
          'socialGroups' => 'socialGroups',
          'userFormatStart' => 'userFormatStart',
          'userFormatEnd' => 'userFormatEnd',
          'password' => 'password',
          'joinDate' => 'joinDate',
          'birthDate' => 'birthDate',
          'lastSync' => 'lastSync',
          'defaultRoom' => 'defaultRoom',
          'interface' => 'interface',
          'favRooms' => 'favRooms',
          'watchRooms' => 'watchRooms',
          'ignoreList' => 'ignoreList',
          'status' => 'status',
          'defaultHighlight' => 'defaultHighlight',
          'defaultColor' => 'defaultColor',
          'defaultFontface' => 'defaultFontface',
          'defaultFormatting' => 'defaultFormatting',
          'settings' => 'settings',
          'userPrivs' => 'userPrivs',
          'adminPrivs' => 'adminPrivs',
        ),
        "{$sqlPrefix}sessions" => array(
          'anonId' => 'anonId',
          'magicHash' => 'magicHash',
          'userId' => 'suserId',
          'time' => array(
            'name' => 'sessionTime',
            'context' => 'time',
          ),
          'ip' => 'sessionIp',
          'browser' => 'sessionBrowser',
        ),
      ),
      array( // Conditions
        'both' => array(
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'userId',
            ),
            'right' => array(
              'type' => 'column',
              'value' => 'suserId',
            ),
          ),
          array(
            'type' => 'e',
            'left' => array(
              'type' => 'column',
              'value' => 'magicHash',
            ),
            'right' => array(
              'type' => 'string',
              'value' => $sessionHash,
            ),
          ),
        ),
      )
    ); die($user);

    if ($user) {
      if ((int) $user['userId'] !== (int) $userIdComp) { // The userid sent has to be the same one in the DB. In theory we could just not require a userId be specified, but there are benefits to this alternative. For instance, this eliminates some forms of injection-based session fixation.

        define('LOGIN_FLAG','INVALID_SESSION');

        $valid = false;
      }
      elseif ($user['sessionBrowser'] !== $_SERVER['HTTP_USER_AGENT']) { // Require the UA match that of the the one used to establish the session. Smart clients are encouraged to specify there own with their client name and vers
        define('LOGIN_FLAG','INVALID_SESSION');

        $valid = false;
      }
      elseif ($user['sessionIp'] !== $_SERVER['REMOTE_ADDR']) {
        define('LOGIN_FLAG','INVALID_SESSION');

        $valid = false;
      }
      else {
        if ($user['anonId']) {
          $anonId = $user['anonId'];
          $anonymous = true;
        }

        $noSync = true;
        $valid = true;

        if ($user['sessionTime'] < time() - 300) { // If five minutes have passed since the session has been generated, update ift.
          $session = 'update';
        }
      }
    }
    else {
      define('LOGIN_FLAG','INVALID_SESSION');

      $valid = false;
    }
  }

  elseif ($userName && $password) {
    $user = dbRows("SELECT * FROM {$sqlUserTable} WHERE $sqlUserTableCols[userName] = '" . dbEscape($userName) . "' LIMIT 1");

    if (processLogin($user,$password,'plaintext')) {
      $valid = true;
      $session = 'create';
    }
    else {
      $valid = false;
    }
  }

  elseif ($userId && $password) {
    $user = dbRows("SELECT * FROM {$sqlUserTable} WHERE $sqlUserTableCols[userId] = " . (int) $userId . ' LIMIT 1');

    if (processLogin($user,$password,'plaintext')) {
      $valid = true;
      $session = 'create';
    }
    else {
      $valid = false;
    }
  }

  elseif ($anonymousUser && $anonymous) {
    $user = dbRows("SELECT * FROM {$sqlUserTable} WHERE $sqlUserTableCols[userId] = " . (int) $userId . ' LIMIT 1');

    $valid = true;
    $api = true;
    $session = 'create';
  }

  else {
    $valid = false;
  }
}

($hook = hook('validate_process') ? eval($hook) : '');





///* Final Forum-Specific Processing *///

if ($valid) { // If the user is valid, process their preferrences.

  if ($noSync || $loginConfig['method'] == 'vanilla') {

  }
  else {
    if ($loginConfig['method'] == 'vbulletin' || $loginConfig['method'] == 'phpbb') {

      $userCopy = $user;
      unset($user);



      /* Set Relevant User Data */

      $user2['userName'] = $userCopy[$sqlUserTableCols['userName']];
      $user2['userId'] = $userCopy[$sqlUserTableCols['userId']];
      $user2['timeZone'] = $userCopy[$sqlUserTableCols['timeZone']];
      $user2['userGroup'] = $userCopy[$sqlUserTableCols['userGroup']];
      $user2['allGroups'] = $userCopy[$sqlUserTableCols['allGroups']];
      $user2['color'] = $userCopy[$sqlUserTableCols['color']];
      $user2['avatar'] = $userCopy[$sqlUserTableCols['avatar']];
    }




    switch ($loginConfig['method']) {

      case 'vbulletin':

      if ($userCopy[$sqlUserTableCols['options']] & 64) $user2[$sqlAdminGroupTableCols['timeZone']]++; // DST is autodetect. We'll just set it by hand.
      elseif ($userCopy[$sqlUserTableCols['options']] & 128) $user2[$sqlAdminGroupTableCols['timeZone']]++; // DST is on, add an hour
      else $user2[$sqlAdminGroupTableCols['timeZone']]; // DST is off

      $group = dbRows("SELECT * FROM $sqlAdminGroupTable WHERE $sqlAdminGroupTableCols[groupId] = $user2[userGroup]");

      $user2['userFormatStart'] = $group[$sqlAdminGroupTableCols['startTag']];
      $user2['userFormatEnd'] = $group[$sqlAdminGroupTableCols['endTag']];
      $user2['avatar'] = $forumUrl . '/image.php?u=' . $user2['userId'];
      $user2['profile'] = $forumUrl . '/member.php?u=' . $user2['userId'];
      break;

      case 'phpbb':
      if ($user2['userGroup']) {
        $group = dbRows("SELECT * FROM $sqlUserGroupTable WHERE $sqlUserGroupTableCols[groupId] = $user2[userGroup]");
      }

      if (!$user2['color']) {
        $user2['color'] = $group[$sqlUserTableCols['color']];
      }

      $user2['userFormatStart'] = "<span style=\"color: #$user2[color]\">";
      $user2['userFormatEnd'] = '</span>';
      if ($user2['avatar']) {
        $user2['avatar'] = $forumUrl . 'download/file.php?avatar=' . $user2['avatar'];
      }
      $user2['profile'] = $forumUrl . 'memberlist.php?mode=viewprofile&u=' . $user2['userId'];
      break;

    }



    ($hook = hook('validate_preprefs') ? eval($hook) : '');


    $userprefs = dbRows("SELECT *
      {$userprefs_select}
    FROM {$sqlPrefix}users
     {$userprefs_users}
    WHERE userId = " . (int) $user2['userId'] . "
      {$userprefs_where}
    {$userprefs_end}");


    if (!$userprefs) {
      // Get Default Priviledges
      $priviledges = 16; // Can post

      if (!$anonymous) { // In theory, you can still manually allow anon users to do the other things.
        if ($userPermissions['roomCreation']) {
          $priviledges += 32;
        }
        if ($userPermissions['privateRoomCreation']) {
          $priviledges += 64;
        }
        if ($userPermissions['roomsOnline']) {
          $priviledges += 1024;
        }
        if ($userPermissions['postCounts']) {
          $priviledges += 2048;
        }
      }

      dbInsert(array(
        'userId' => (int) $user2['userId'],
        'userName' => dbEscape($user2['userName']),
        'userGroup' => (int) $user2['userGroup'],
        'allGroups' => dbEscape($user2['allGroups']),
        'userFormatStart' => dbEscape($user2['userFormatStart']),
        'userFormatEnd' => dbEscape($user2['userFormatEnd']),
        'avatar' => dbEscape($user2['avatar']),
        'profile' => dbEscape($user2['profile']),
        'socialGroups' => dbEscape($socialGroups['groups']),
        'userPrivs' => (int) $priviledges,
        'lastSync' => array(
          'type' => 'raw',
          'value' => 'NOW()',
        ),
      ),"{$sqlPrefix}users");

      $userprefs = dbRows('SELECT * FROM ' . $sqlPrefix . 'users WHERE userId = ' . (int) $user2['userId']);

      /* Update Social Groups */
      $socialGroups = dbRows("SELECT GROUP_CONCAT($sqlMemberGroupTableCols[groupId] SEPARATOR ',') AS groups FROM {$sqlMemberGroupTable} WHERE {$sqlMemberGroupTableCols[userId]} = $user2[userId] AND $sqlMemberGroupTableCols[type] = '$sqlMemberGroupTableCols[validType]'");

      dbUpdate(array(
        'userName' => $user2['userName'],
        'userGroup' => $user2['userGroup'],
        'allGroups' => $user2['allGroups'],
        'userFormatStart' => $user2['userFormatStart'],
        'userFormatStart' => $user2['userFormatStart'],
        'avatar' => $user2['avatar'],
        'profile' => $user2['profile'],
        'socialGroups' => $socialGroups['groups'],
        'lastSync' => array(
          'type' => 'raw',
          'value' => 'NOW()',
        ),
      ),
      "{$sqlPrefix}users",
      array(
        'userId' => (int) $user2['userId'],
      ));
    }
    elseif ($userprefs['lastSync'] <= (time() - ($sync ? $sync : (0)))) { // This updates various caches every so often. In general, it is a rather slow process, and as such does tend to take a rather long time (that is, compared to normal - it won't exceed 500miliseconds, really).

      /* Favourite Room Cleanup
      * Remove all favourite groups a user is no longer a part of. */
      if ($user['favRooms']) {
        $stop = false;

        $favRooms = dbRows("SELECT * FROM {$sqlPrefix}rooms WHERE options & 4 = FALSE AND roomId IN ($user[favRooms])",'id');

        foreach ($favRooms AS $id => $room2) {
          eval(hook('templateFavRoomsEachStart'));

          if (!fim_hasPermission($room2,$user,'view') && !$stop) {
            $currentRooms = explode(',',$user['favRooms']);
            foreach ($currentRooms as $room3) if ($room3 != $room2['roomId'] && $room3 != '') {
              $currentRooms2[] = (int) $room3; // Rebuild the array without the room ID.
            }


            dbUpdate(array(
              'favRooms' => $newRoomString
            ),
            "{$sqlPrefix}users",
            array(
              'userId' => $user['userId'],
            ));

            $stop = false;

            continue;
          }
        }
      }

      /* Update Social Groups */
      $socialGroups = dbRows("SELECT GROUP_CONCAT($sqlMemberGroupTableCols[groupId] SEPARATOR ',') AS groups FROM {$sqlMemberGroupTable} WHERE {$sqlMemberGroupTableCols[userId]} = $user2[userId] AND $sqlMemberGroupTableCols[type] = '$sqlMemberGroupTableCols[validType]'");

      dbUpdate(array(
        'userName' => $user2['userName'],
        'userGroup' => $user2['userGroup'],
        'allGroups' => $user2['allGroups'],
        'userFormatStart' => $user2['userFormatStart'],
        'userFormatStart' => $user2['userFormatStart'],
        'avatar' => $user2['avatar'],
        'profile' => $user2['profile'],
        'socialGroups' => $socialGroups['groups'],
        'lastSync' => array(
          'type' => 'raw',
          'value' => 'NOW()',
        ),
      ),
      "{$sqlPrefix}users",
      array(
        'userId' => (int) $user2['userId'],
      ));

      $userprefs = dbRows('SELECT * FROM ' . $sqlPrefix . 'users WHERE userId = ' . (int) $user2['userId']); // Should be merged into the above $user query, but because the two don't automatically sync for now it can't be. A manual sync, plus setting up the userpref row in the first event would fix this.
    }

    $user = array_merge($user2,$userprefs); // Merge userprefs into user for future referrence.
  }




  if ($session == 'create') {
    ($hook = hook('validate_createsession') ? eval($hook) : '');

    $sessionHash = fim_generateSession();

    $anonId = rand(1,10000);

    dbInsert(array(
      'userId' => $user['userId'],
      'anonId' => ($anonymous ? $anonId : 0),
      'time' => array(
        'type' => 'raw',
        'value' => 'NOW()',
      ),
      'magicHash' => dbEscape($sessionHash),
      'browser' => $_SERVER['HTTP_USER_AGENT'],
      'ip' => $_SERVER['REMOTE_ADDR'],
    ),"{$sqlPrefix}sessions");

    // Whenever a new user logs in, delete all sessions from 15 or more minutes in the past.
    dbDelete("{$sqlPrefix}sessions",array(
      'time' => array(
        'type' => 'raw', // Data in the value column should not be escaped.
        'cond' => 'lte', // Comparison is "<="
        'context' => 'time', // We are comparing two times; the column should be processed as a timestamp.
        'value' => 'UNIX_TIMESTAMP(NOW()) - 900',
      ),
    ));
  }

  elseif ($session == 'update' && $sessionHash) {
    ($hook = hook('validate_updatesession') ? eval($hook) : '');

    dbUpdate(array(
      'time' => array(
        'type' => 'raw',
        'value' => 'NOW()',
      ),
    ),
    "{$sqlPrefix}sessions",
    array(
      "magicHash" => $sessionHash,
    ));
  }

  else {
    // I dunno...
  }



  if ($anonymous) {
    $user['userName'] .= $anonId;
  }

}

else {
  unset($user);

  $user = array(
    'userId' => ($anonymousUser ? $anonymousUser : 0),
    'settingsOfficialAjax' => 11264, // Default. TODO: Update w/ config defaults.
    'adminPrivs' => 0, // Nothing
    'userPrivs' => 16, // Allowed, but nothing else.
  );

  ($hook = hook('validate_loginInvalid') ? eval($hook) : '');
}





/* The following defines each individual user's options via an associative array. It is highly recommended this be used to referrence settings. */


if (is_array($loginConfig['superUsers'])) {
  if (in_array($user['userId'],$loginConfig['superUsers'])) {
    $user['adminPrivs'] = 65535; // Super-admin, away!!!! (this defines all bitfields up to 32768)
  }
}

$user['adminDefs'] = array(
  'modPrivs' => ($user['adminPrivs'] & 1), // This effectively allows a user to give himself everything else below
  'modCore' => ($user['adminPrivs'] & 2), // This is the "untouchable" flag, but that's more or less all it means.
  'modUsers' => ($user['adminPrivs'] & 16), // Ban, Unban, etc.
  'modImages' => ($user['adminPrivs'] & 64), // File Uploads
  'modCensor' => ($user['adminPrivs'] & 256), // Censor

  /* Should Generally Go Together */
  'modPlugins' => ($user['adminPrivs'] & 4096), // Plugins
  'modTemplates' => ($user['adminPrivs'] & 8192), // Templates
  'modHooks' => ($user['adminPrivs'] & 16384), // Hooks
);


$user['userDefs'] = array(
  'allowed' => ($user['userPrivs'] & 16), // Is not banned
  'createRooms' => ($user['userPrivs'] & 32), // May create rooms
  'privateRooms' => ($user['userPrivs'] & 64), // May create private rooms

  'roomsOnline' => ($user['userPrivs'] & 1024), // May see rooms online (API-handled).
  'postCounts' => ($user['userPrivs'] & 2048), // May see post counts (API-handled).
);


if ($valid) {

  /* General "Hard" Ban Generation (If $banned, the user will have no permissions) */

  if ($bannedUserGroups) { // The user is in a usergroup that is banned.
    if (fim_inArray($bannedUserGroups,explode(',',$user['allGroups']))) {
      $banned = true;
    }
  }

  elseif (!$user['userDefs']['allowed']) { // The user is not allowed to access the chat.
    $banned = true;
  }


  if ($user['adminDefs']['modCore']) { // The user is an admin, don't give a crap about the above!
    $banned = false;
  }

  ($hook = hook('validate_loginValid') ? eval($hook) : '');

}



if ($api) {

  switch (LOGIN_FLAG) { // Generate a message based no the LOGIN_FLAG constant (...this should probably be a variable since it changes, but meh - it seems more logical as such)
    case 'PASSWORD_ENCRYPT':
    $errDesc = 'The password encryption used was not recognized and could not be decoded.';
    break;

    case 'BAD_USERNAME':
    $errDesc = 'The user was not recognized.';
    break;

    case 'BAD_PASSWORD':
    $errDesc = 'The password was not correct.';
    break;

    case 'API_VERSION_STRING':
    $errDesc = 'The API version string specified is not recognized.';
    break;

    case 'DEPRECATED_VERSION':
    $errDesc = 'The API version specified is deprecated and may no longer be used.';
    break;

    case 'INVALID_SESSION':
    $errDesc = 'The specified session is no longer valid.';
    break;
  }

  if (!$valid && !defined('LOGIN_FLAG')) { // Generic login flag
    define('LOGIN_FLAG',INVALID_LOGIN);

    $errDesc = 'The login was incorrect.';
  }


  $xmlData = array(
    'login' => array(
      'valid' => (bool) $valid,

      'loginFlag' => (defined('LOGIN_FLAG') ? LOGIN_FLAG : ''),
      'loginText' => $errDesc,

      'sessionHash' => $sessionHash,
      'anonId' => ($anonymous ? $anonId : 0),
      'defaultRoomId' => (int) ($_GET['room'] ? $_GET['room'] :
        ($user['defaultRoom'] ? $user['defaultRoom'] :
          ($defaultRoom ? $defaultRoom : 1))), // Get the room we're on. If there is a $_GET variable, use it, otherwise the user's "default", or finally just main.

      'userData' => array(
        'userName' => ($user['userName']),
        'userId' => (int) $user['userId'],
        'userGroup' => (int) $user['userGroup'],
        'avatar' => ($user['avatar']),
        'profile' => ($user['profile']),
        'socialGroups' => ($user['socialGroups']),
        'startTag' => ($user['userFormatStart']),
        'endTag' => ($user['userFormatEnd']),
        'defaultFormatting' => array(
          'color' => ($user['defaultColor']),
          'highlight' => ($user['defaultHighlight']),
          'fontface' => ($user['defaultFontface']),
          'general' => (int) $user['defaultGeneral']
        ),
      ),

      'userPermissions' => $user['userDefs'],
      'adminPermissions' => $user['adminDefs'],
    ),
  );


  ($hook = hook('validate_api') ? eval($hook) : '');

  echo fim_outputApi($xmlData);

  die();
}


($hook = hook('validate_end') ? eval($hook) : '');
?>