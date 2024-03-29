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
 * Container Template
 *
 * @param string $title
 * @param string $content
 * @param string $class
 * @return string
 * @author Joseph Todd Parsons <josephtparsons@gmail.com>
 */
function container($title, $content, $class = 'page') {
  global $config;

  return $return = "<table class=\"$class ui-widget\">
  <thead>
    <tr class=\"hrow ui-widget-header ui-corner-top\">
      <td>$title</td>
    </tr>
  </thead>
  <tbody class=\"ui-widget-content ui-corner-bottom\">
    <tr>
      <td>
        <div>$content</div>
      </td>
    </tr>
  </tbody>
</table>

";
}


function formatXmlString($xml) {

  $xml = preg_replace('/(>)(<)(\/*)/', "$1\n$2$3", $xml); // add marker linefeeds to aid the pretty-tokeniser (adds a linefeed between all tag-end boundaries)
  $xml = preg_replace('/^\s+/m','', $xml); // Get rid of all spaces at the beginning of lines.

  // now indent the tags
  $token      = strtok($xml, "\n");
  $result     = ''; // holds formatted version as it is built
  $pad        = 0; // initial indent
  $matches    = array(); // returns from preg_matches()

  // scan each line and adjust indent based on opening/closing tags
  while ($token !== false) {

    // test for the various tag states

    if (preg_match('/.+<\/\w[^>]*>$/', $token, $matches)) { $indent = 0; } // 1. open and closing tags on same line - no change
    elseif (preg_match('/\<\!\-\-(.+?)\-\-\>$/', $token, $matches)) { $indent = 0; } // 2. closing tag - outdent now
    elseif (preg_match('/^<\/\w/', $token, $matches)) { $pad--; $indent = 0; } // 3. opening tag - don't pad this one, only subsequent tags
    elseif (preg_match('/^<[^>]*[^\/]>.*$/', $token, $matches)) { $indent = 1; } // 4. no indentation needed
    else { $indent = 0; }

    // pad the line with the required number of leading spaces
    $line    = str_pad($token, strlen($token) + $pad, ' ', STR_PAD_LEFT);
    $result .= $line . "\n"; // add to the cumulative result, with linefeed
    $token   = strtok("\n"); // get the next token
    $pad    += $indent; // update the pad size for subsequent lines
  }

  return $result;
}

function fimHtml_buildSelect($selectName, $selectArray, $selectedItem) {
  $code = "<select name=\"$selectName\">";

  foreach ($selectArray AS $key => $value) {
    $code .= "<option value=\"$key\"" . ($key === $selectedItem ? ' selected="selected"' : '') . ">$value</option>";
  }

  $code .= '</select>';

  return $code;
}
?>
