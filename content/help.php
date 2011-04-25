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


$reqPhrases = true;
$reqHooks = true;

require_once('../global.php'); // Used for everything.

echo '<script type="text/javascript">
$(document).ready(function() {
  $(\'#help\').accordion({clearStyle: true});
});
</script>' . str_ireplace(array('{{bbcodeBlock}}','{{debugBlock}}'),array(($bbcode['shortCode'] ? '<li><em>+[text]+</em> - Bold a message.</li>
<li><em>_[text]_</em> - Underline a message.</li>
<li><em> /[text]/ </em> - Italicize a message.</li>
<li><em>=[text]=</em> - Strikethrough a message.</li>' : '') . ($bbcode['buis'] ? '
<li><em>[b][text][/b]</em> - Bold a message.</li>
<li><em>[u][text][/u]</em> - Underline a message.</li>
<li><em>[i][text][/i]</em> - Italicize a message.</li>
<li><em>[s][text][/s]</em> - Strikethrough a message.</li>' : '') . ($bbcode['link'] ? '
<li><em>[url]http://example.com/[/url]</em> - Link a URL.</li>
<li><em>[url=http://example.com/]Example[/url]</em> - Link a URL</li>' : '') . ($bbcode['image'] ? '
<li><em>[img]http://example.com/image.png[/img]</em> - Link an Image.</li>
<li><em>[img=":P"]http://example.com/image.png[/img]</em> - Link an image with alt text.</li>' : '') . ($bbcode['video'] ? '
<li><em>[youtube]{youtubeCode}[/youtube]</em> - Include a Youtube video.</li>' : ''),'<em>User Agent</em>: ' . $_SERVER['HTTP_USER_AGENT'] . '<br />
<em>Style Cookie</em>: ' . $_COOKIE['bbstyleid'] . '<br />
<em>Sessionhash Cookie</em>: ' . $_COOKIE['bbsessionhash'] . '<br />
<em>BBCode Array</em>: ' . print_r($bbcode,true) . '<br />
<em>Parse Flags</em>: ' . ($parseFlags ? 'On' : 'Off') . '<br />
<em>Login Method</em>: ' . $loginMethod . '<br />'),$phrases['help']);
?>