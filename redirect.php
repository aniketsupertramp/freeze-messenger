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


require('global.php');


/* Get Request Data */
$request = fim_sanitizeGPC('g', array(
  'do' => array(
    'cast' => 'string',
    'valid' => array('register'),
    'require' => false,
  ),
));


$redirectPage = ''; // This will contain the page to redirect to.


switch ($request['do']) {

  case 'register': // Register for an account to post.

    switch ($loginConfig['method']) { // Different methods for each forum system.
      case 'phpbb':     $redirectPage = $loginConfig['url'] . 'ucp.php?mode=register'; break;
      case 'vbulletin': $redirectPage = $loginConfig['url'] . 'register.php';          break;
      case 'vanilla':   $redirectPage = 'register/index.php';                          break; // Clients can do this themselves if they want, be we will include our own as a sorta "core" functionality.
    }

  break;

}


if ($redirectPage) {
  header('Location: ' . $redirectPage);
  die('Redirecting to <a href="' . $redirectPage . '">' . $redirectPage . '</a>');
}
else {
  die('No action detected.');
}
?>
