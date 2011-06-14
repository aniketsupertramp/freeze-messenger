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

CREATE TABLE IF NOT EXISTS `{prefix}fonts` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `data` varchar(500) NOT NULL,
  `category` varchar(20) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE={engine} DEFAULT CHARSET=utf8;

-- DIVIDE

INSERT INTO `{prefix}fonts` (`id`, `name`, `data`, `category`) VALUES
(1, 'FreeMono', 'FreeMono, TwlgMono, ''Courier New'', Consolas, monospace', 'monospace'),
(2, 'Courier New', '''Courier New'', FreeMono, TwlgMono, Consolas, Courier, monospace', 'monospace'),
(3, 'Consolas', 'Consolas, ''Courier New'', FreeMono, TwlgMono, monospace', 'monospace'),
(4, 'Courier', 'Courier, ''Courier New'', Consolas, monospace', 'monospace'),
(5, 'Liberation Mono', '''Liberation Mono'', monospace', 'monospace'),
(6, 'Lucida Console', '''Lucida Console'', ''Lucida Sans Typewriter'', monospace', 'monospace'),
(7, 'Times New Roman', '''Times New Roman'', ''Liberation Serif'', Georgia, FreeSerif, Cambria, serif', 'serif'),
(8, 'Liberation Serif', '''Liberation Serif'', FreeSerif, ''Times New Roman'', Georgia, Cambria, serif', 'serif'),
(9, 'Georgia', 'Georgia, Cambria, ''Liberation Serif'', ''Times New Roman'', serif', 'serif'),
(10, 'Cambria', 'Cambria, Georgia, ''Liberation Serif'', ''Times New Roman'', serif', 'serif'),
(11, 'Segoe UI', '''Segoe UI'', serif', 'serif'),
(12, 'Garamond', 'Garamond, serif', 'serif'),
(13, 'Century Gothic', '''Century Gothic'', Ubuntu, sans-serif', 'sans-serif'),
(14, 'Trebuchet MS', '''Trebuchet MS'', Arial, Tahoma, Verdana, FreeSans, sans-serif', 'sans-serif'),
(15, 'Arial', 'Arial, ''Trebuchet MS'', Tahoma, Verdana, FreeSans, sans-serif', 'sans-serif'),
(16, 'Verdana', 'Arial, Verdana, ''Trebuchet MS'', Tahoma, Arial, sans-serif', 'sans-serif'),
(17, 'Tahoma', 'Tahoma, Verdana, ''Trebuchet MS'', Arial, FreeSans, sans-serif', 'sans-serif'),
(18, 'Ubuntu', 'Ubuntu, FreeSans, Tahoma, sans-serif', 'sans-serif'),
(19, 'Liberation Sans', 'Liberation Sans, sans-serif', 'sans-serif'),
(20, 'Bauhaus 93', '''Bauhaus 93'', fantasy', 'fantasy'),
(21, 'Jokerman', 'Jokerman, fantasy', 'fantasy'),
(22, 'Impact', 'Impact, fantasy', 'fantasy'),
(23, 'Papyrus', 'Papyrus, fantasy', 'fantasy'),
(24, 'Copperplate Gothic B', '''Copperplate Gothic Bold'', fantasy', 'fantasy'),
(25, 'Rockwell Extra Bold', '''Rockwell Extra Bold'', fantasy', 'fantasy'),
(26, 'Wargames', 'Wargames, fantasy', 'fantasy'),
(27, 'Wintermute', 'Wintermute, fantasy', 'fantasy'),
(28, 'You''re Gone', '''You\\''re Gone'', fantasy', 'fantasy'),
(29, 'Yawnovision', 'Yawnovision, fantasy', 'fantasy'),
(30, 'Wild Sewage', '''Wild Sewage'', fantasy', 'fantasy'),
(31, 'Vanilla Whale', '''Vanilla Whale'', fantasy', 'fantasy'),
(32, 'Vectoid', 'Vectoid, fantasy', 'fantasy'),
(33, 'Webster World', '''Webster World'', fantasy', 'fantasy'),
(34, 'Vademcum', 'Vademcum, fantasy', 'fantasy'),
(35, 'Sybil Green', '''Sybil Green'', fantasy', 'fantasy'),
(36, 'SuperHeterodyne', 'SuperHeterodyne, fantasy', 'fantasy'),
(37, 'Sudbury Basin 3D', '''Sudbury Basin 3D'', fantasy', 'fantasy'),
(38, 'Street Cred', '''Street Cred'', fantasy', 'fantasy'),
(39, 'Stich & Bitch', '''Stich & Bitch'', fantasy', 'fantasy'),
(40, 'Still Time', '''Still Time'', fantasy', 'fantasy'),
(41, 'Soul Mama', '''Soul Mama'', fantasy', 'fantasy'),
(42, 'Planet Benson 2', '''Planet Benson 2'', fantasy', 'fantasy'),
(43, 'Pastor of Muppets', '''Pastor of Muppets'', fantasy', 'fantasy'),
(44, 'Pants Patrol', '''Pants Patrol'', fantasy', 'fantasy'),
(45, 'Neurochrome', 'Neurochrome, fantasy', 'fantasy'),
(46, 'Nasalization', 'Nasalization, fantasy', 'fantasy'),
(47, 'Mexcellent 3D', '''Mexcellent 3D'', fantasy', 'fantasy'),
(48, 'Metal Lord', '''Metal Lord'', fantasy', 'fantasy'),
(49, 'Map Of You', '''Map Of You'', fantasy', 'fantasy'),
(50, 'Joystix', 'Joystix, fantasy', 'fantasy'),
(51, 'Joy Circuit', '''Joy Circuit'', fantasy', 'fantasy'),
(52, 'Home Sweet Home', '''Home Sweet Home'', fantasy', 'fantasy'),
(53, 'Hawkeye', 'Hawkeye, fantasy', 'fantasy'),
(54, 'Gunplay 3D', '''Gunplay 3D'', fantasy', 'fantasy'),
(55, 'Graffiti Tryat', '''Graffiti Tryat'', fantasy', 'fantasy'),
(56, 'Glazkrak', 'Glazkrak, fantasy', 'fantasy'),
(57, 'Ghostmeat', 'Ghostmeat, fantasy', 'fantasy'),
(58, 'Ethnocentric', 'Ethnocentric, fantasy', 'fantasy'),
(59, 'Endless Showroom', '''Endless Showroom'', fantasy', 'fantasy'),
(60, 'Edmunds', 'Edmunds, fantasy', 'fantasy'),
(61, 'Edgewater', 'Edgewater, fantasy', 'fantasy'),
(62, 'Earwig Factory', '''Earwig Factory'', fantasy', 'fantasy'),
(63, 'Degrassi', 'Degrassi, fantasy', 'fantasy'),
(64, 'Crackman', 'Crackman, fantasy', 'fantasy'),
(65, 'Burnstown Dam', '''Burnstown Dam'', fantasy', 'fantasy'),
(66, 'Astron Boy Video', '''Astron Boy Video'', fantasy', 'fantasy'),
(67, 'Lucida Sans Handwrit', '''Lucida Sans Handwritten'', cursive', 'cursive'),
(68, 'Comic Sans MS', '''Comic Sans MS'', cursive', 'cursive'),
(69, 'Lucida Sans Handwrit', '''Lucida Sans Handwritten'', cursive', 'cursive'),
(70, 'Curlz MT', '''Curlz MT'', cursive', 'cursive'),
(71, 'Freestyle Script', '''Freestyle Script'', cursive', 'cursive'),
(72, 'Edwardian Script ITC', '''Edwardian Script ITC'', cursive', 'cursive');
