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

/*
 * This is WebPro's means of configuring FIM's data. The pages for individual actions are stored in the moderate/ directory.
*/


define('WEBPRO_INMOD', true); // Security to prevent loading of base moderate pages.

/* This below bit hooks into the validate.php script to facilitate a seperate login. It is a bit cooky, though, and will need to be further tested. */
if (isset($_POST['webproModerate_userName'])) {
  $hookLogin['userName'] = $_POST['webproModerate_userName'];
  $hookLogin['password'] = $_POST['webproModerate_password'];
}
elseif (isset($_COOKIE['webproModerate_sessionHash'])) {
  $hookLogin['sessionHash'] = $_COOKIE['webproModerate_sessionHash'];
  $hookLogin['userId'] = $_COOKIE['webproModerate_userId'];
}
else {
  $ignoreLogin = true;
}


/* Here we require the backend. */

try {
  require('../global.php');
} catch (Exception $e) {
  $message = $e->getMessage();
}
require('moderateFunctions.php'); // Functions that are used solely by the moderate interfaces.


/* This sets the cookie with the session hash if possible (sessionHash will be set in validate.php). */
if (isset($sessionHash) && strlen($sessionHash) > 0) {
  setcookie('webproModerate_sessionHash', $sessionHash);
  setcookie('webproModerate_userId', $user['userId']);
}
?><!DOCTYPE HTML>
<!-- Original Source Code Copyright © 2011 Joseph T. Parsons. -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>Freeze Messenger AdminCP</title>
  <meta name="robots" content="noindex, nofollow" />
  <meta name="author" content="Joseph T. Parsons" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <link rel="icon" id="favicon" type="image/png" href="images/favicon.png" />
  <!--[if lte IE 9]>
  <link rel="shortcut icon" id="faviconfallback" href="images/favicon1632.ico" />
  <![endif]-->

  <!-- START Styles -->
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/absolution/jquery-ui-1.8.16.custom.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/absolution/fim.css" media="screen" />
  <link rel="stylesheet" type="text/css" href="../webpro/client/css/stylesv2.css" media="screen" />

  <link rel="stylesheet" type="text/css" href="./client/codemirror/lib/codemirror.css">
  <!--<link rel="stylesheet" type="text/css" href="./client/codemirror/mode/xml/xml.css">
  <link rel="stylesheet" type="text/css" href="./client/codemirror/mode/clike/clike.css">-->

  <style>
  *, *:before, *:after {
    -moz-box-sizing: border-box; -webkit-box-sizing: border-box; box-sizing: border-box;
  }

  body {
    padding: 5px;
  }

  #moderateRight {
    float: right;
    width: 75%;
    overflow: auto;
  }

  #moderateLeft {
    float: left;
    width: 25%;
    padding-right: 20px;
  }

  .CodeMirror {
    border: 1px solid white;
    background-color: white;
    color: black;
    width: 100%;
    min-width: 200px !important;
  }

  .searched {background: yellow;}
  .mustache {color: #0ca;}

  h1 {
    margin: 0px;
    padding: 5px;
  }

  .main {
    max-width: 1000px;
    margin-left: auto;
    margin-right: auto;
    display: block;
    border: 1px solid black;
  }

  .ui-widget {
    font-size: 12px;
  }
  .ui-widget-content {
    padding: 5px;
  }
  .uninstalledFlag {
    font-weight: bold;
  }
  abbr {
    outline-bottom: dotted 1px;
  }
  pre {
    display: inline;
  }

  /* General Tables */
  table td {
    padding-top: 5px;
    padding-bottom: 5px;
  }
  table tr {
    border-bottom: 1px solid black;
  }
  table {
    border-collapse: collapse;
  }
  table tr:last-child {
    border-bottom: none;
  }
  tbody tr:nth-child(2n) {
    background: #efefef !important;
  }
  table.page td {
    padding: 5px;
  }
  table.page tr.hrow {
    font-size: 2em;
    font-weight: bold;
  }

  /*** Mobile Screen Layout ***/
  @media screen and (max-width: 600px) {
    #moderateLeft, #moderateRight {
      clear: both;
      float: none;
      width: 100%;
    }

    #moderateLeft {
      padding-right: 0px;
      padding-bottom: 20px;
    }

    table.page tr.hrow {
      font-size: 1.4em;
    }
  }
  </style>
  <!-- END Styles -->


  <!-- START Scripts -->
  <script src="./client/js/jquery-1.6.2.min.js" type="text/javascript"></script>

  <script src="./client/js/jquery-ui-1.8.16.custom.min.js" type="text/javascript"></script>
  <script src="./client/js/jquery.plugins.js" type="text/javascript"></script>


  <script src="./client/codemirror/lib/codemirror.js"></script>
  <script src="./client/codemirror/mode/xml/xml.js"></script>
  <script src="./client/codemirror/mode/clike/clike.js"></script>

  <script>
  function windowDraw() {
    $('button, input[type=button], input[type=submit]').button();

    $('#mainMenu').accordion({
      autoHeight : false,
      active : Number($.cookie('webproModerate_menustate')) - 1,
      change: function(event, ui) {
        var sid = ui.newHeader.children('a').attr('data-itemId');

        $.cookie('webproModerate_menustate', sid, { expires: 14 });
      }
    });
  }

  $(document).ready(function() {
    if ($('#textXml').size()) {
      var editorXml = CodeMirror.fromTextArea(document.getElementById("textXml"), {
        mode:  "text/html",
        lineNumbers: true,
        lineWrapping: true
      });
    }

    if ($('#textClike').size()) {
      var editorClike = CodeMirror.fromTextArea(document.getElementById("textClike"), {
        mode:  "clike",
        lineNumbers: true,
        lineWrapping: true
      });
    }

    windowDraw();
  });


  $(window).bind('resize', windowDraw);


  var alert = function(text) {
    dia.info(text, "Alert");
  };
  </script>
  <!-- END Scripts -->

</head>
<body>
<div id="moderateLeft">
  <div id="mainMenu">
    <h3><a href="#" data-itemId="1">General Information</a></h3>
    <ul>
      <li><a href="moderate.php?do=main">Home</a></li>
      <li><a href="moderate.php?do=copyright">Copyright</a></li>
    </ul>

    <?php if ($user['adminDefs']['modTemplates']) { ?>
    <h3><a href="#" data-itemId="2">WebPro</a></h3>
    <ul>
      <li><a href="moderate.php?do=phrases">Modify Phrases</a></li>
      <li><a href="moderate.php?do=templates">Modify Templates</a></li>
    </ul>
    <?php } ?>

    <h3><a href="#" data-itemId="3">Engines</a></h3>
    <ul>
      <?php echo ($user['adminDefs']['modCensor'] ? '<li><a href="moderate.php?do=censor">Modify Censor</a></li>' : ''); ?>
      <?php echo ($user['adminDefs']['modEmotes'] ? '<li><a href="moderate.php?do=emoticons">Modify Emoticons</a></li>' : ''); ?>
      <?php echo ($user['adminDefs']['modPlugins'] && false ? '<li><a href="moderate.php?do=plugins">Modify Plugins</a></li>' : ''); ?>
    </ul>

    <h3><a href="#" data-itemId="4">Moderation</a></h3>
    <ul>
      <?php echo ($user['adminDefs']['modUsers'] ? '<li><a href="moderate.php?do=users">Manage Users</a></li>' : ''); ?>
      <?php echo ($user['adminDefs']['modRooms'] ? '<li><a href="moderate.php?do=rooms">Manage Rooms</a></li>' : ''); ?>
      <?php echo ($user['adminDefs']['modPrivate'] ? '<li><a href="moderate.php?do=private">Manage Private</a></li>' : ''); ?>
      <?php echo ($user['adminDefs']['modFiles'] ? '<li><a href="moderate.php?do=files">Manage Files</a></li>' : ''); ?>
    </ul>

    <?php if ($user['adminDefs']['modPrivs']) { ?>
    <h3><a href="#" data-itemId="5">Advanced</a></h3>
    <ul>
      <li><a href="moderate.php?do=admin">Admin Permissions</a></li>
      <li><a href="moderate.php?do=sessions">User Sessions</a></li>
      <li><a href="moderate.php?do=config">Configuration Editor</a></li>
      <li><a href="moderate.php?do=sys">System Check</a></li>
      <li><a href="moderate.php?do=tools">Tools</a></li>
      <li><a href="moderate.php?do=phpinfo">PHP Info</a></li>
    </ul>
    <?php } ?>
  </div>
</div>
<div id="moderateRight" class="ui-widget">

<?php
if (!$user['userId']) {
  echo container('Please Login',($message ? $message : 'You have not logged in. Please login:') . '<br /><br />

  <form action="moderate.php" method="post">
    <table>
      <tr>
        <td>Username: </td>
        <td><input type="text" name="webproModerate_userName" /></td>
      </tr>
      <tr>
        <td>Password: </td>
        <td><input type="password" name="webproModerate_password" /></td>
      </tr>
      <tr>
        <td colspan="2" align="center"><input type="submit" value="Login" /></td>
      </tr>
    </table>
  </form>');
}
elseif ($user['adminDefs']) { // Check that the user is an admin.
  switch ($_GET['do']) {
    case 'phrases': require('./moderate/phrases.php'); break;
    case 'templates': require('./moderate/templates.php'); break;

    case 'plugins': require('./moderate/plugins.php'); break;
    case 'censor': require('./moderate/censor.php'); break;
    case 'emoticons': require('./moderate/emoticons.php'); break;

    case 'users': require('./moderate/users.php'); break;
    case 'rooms': require('./moderate/rooms.php'); break;
    case 'private': require('./moderate/private.php'); break;
    case 'files': require('./moderate/files.php'); break;

    case 'admin': require('./moderate/admin.php'); break;
    case 'sessions': require('./moderate/sessions.php'); break;
    case 'config': require('./moderate/config.php'); break;
    case 'sys': require('./moderate/status.php'); break;
    case 'tools': require('./moderate/tools.php'); break;
    case 'phpinfo': require('./moderate/phpinfo.php'); break;

    case 'copyright': require('./moderate/copyright.php'); break;
    default: require('./moderate/main.php'); break;
  }
}
else {
  trigger_error('You do not have permission to access this page. Please login on the main chat and refresh.',E_USER_ERROR);
}
?>
</div>
</body>
</html>