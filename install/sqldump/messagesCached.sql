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

CREATE TABLE IF NOT EXISTS `{prefix}messagesCached` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `messageId` int(10) NOT NULL,
  `roomId` int(10) NOT NULL,
  `userId` int(10) NOT NULL,
  `userName` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `avatar` varchar(1000) NOT NULL,
  `profile` varchar(1000) NOT NULL,
  `userGroup` int(10) NOT NULL DEFAULT 1,
  `allGroups` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `userFormatStart` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `userFormatEnd` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `defaultFormatting` int(10) NOT NULL,
  `defaultHighlight` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `defaultColor` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `defaultFontface` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `htmlText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `apiText` varchar(5000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `flag` varchar(10) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;