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

CREATE TABLE IF NOT EXISTS `{prefix}fileVersions` (
  `versionId` int(10) NOT NULL AUTO_INCREMENT,
  `fileId` int(10) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `size` int(10) NOT NULL,
  `md5hash` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `sha256hash` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `salt` int(10) NOT NULL,
  `iv` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `contents` longtext NOT NULL,
  PRIMARY KEY (`versionId`),
  KEY `fileId` (`fileId`)
) ENGINE={engine} DEFAULT CHARSET=utf8;
