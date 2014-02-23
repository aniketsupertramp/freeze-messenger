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

/**** BRIEF INTRODUCTION ****/
/* This file is the MySQL-version (and the only one currently existing) of a generic database layer created for FreezeMessenger. What purpose could it possibly serve? Why not go the PDO-route? Mainly, it offers a few distinct advantages: full control, easier to modify by plugins (specifically, in that most data is stored in a tree structure), and perhaps more importantly it allows things that PDO, which is fundamentally an SQL extension, doesn't. There is no shortage of database foundations bearing almost no semblance to SQL: IndexedDB (which has become popular by-way of web-browser implementation), Node.JS (which I would absolutely love to work with but currently can't because of the MySQL requirement), and others come to mind.
 * As with everything else, this is GPL-based, but if anyone decides they like it and may wish to use it for less restricted purposes, contact me. I have considered going LGPL/MIT/BSD with it, but not yet :P */
 
/**** EXTENSIBILITY NOTES ****/
/* databaseSQL, unlike database, does not support extended languages. The class attempts to handle all variations of SQL (though, at the moment, obviously doesn't -- there will certainly need to be driver-specific code added). As a result, nearly all query strings are abstracted in some way or another with the hope that different SQL variations can be better accomodated with the least amount of code. That said, if a variation is to be added, it needs to be added to databaseSQL. */

/**** SUPPORT NOTES ****/
/* The following is a basic changelog of key MySQL features since 4.1, without common upgrade conversions, slave changes, and logging changes included:
 * 4.1.0: Database, table, and column names are now stored UTF-8 (previously ASCII).
 * 4.1.0: Binary values are treated as strings instead of numbers by default now. (use CAST())
 * 4.1.0: DELETE statements no longer require that named tables be used instead of aliases (e.g. "DELETE t1 FROM test AS t1, test2 WHERE ..." previously had to be "DELETE test FROM test AS t1, test2 WHERE ...").
 * 4.1.0: LIMIT can no longer be negative.
 * 4.1.1: User-defined functions must contain xxx_clear().
 * 4.1.2: When comparing strings, the shorter string will now be right-padded with spaces. Previously, spaces were truncated entirely. Indexes should be rebuilt as a result.
 * 4.1.2: Float previously allowed higher values than standard. While previously FLOAT(3,1) could be 100.0, it now must not exceed 99.9. 
 * 4.1.2: When using "SHOW TABLE STATUS", the old column "type" is now "engine".
 * 4.1.3: InnoDB indexes using latin1_swedish_ci from 4.1.2 and earlier should be rebuilt (using OPTIMIZE).
 * 4.1.4: Tables with TIMESTAMP columns created between 4.1.0 and 4.1.3 must be rebuilt.
 * 5.0.0: ISAM removed. Do not use. (To update, run "ALTER TABLE tbl_name ENGINE = MyISAM;")
 * 5.0.0: RAID features in MySIAM removed.
 * 5.0.0: User variables are not case sensitive in 5.0, but were prior.
 * 5.0.2: "SHOW STATUS" behavior is global before 5.0.2 and sessional afterwards. 'SHOW /*!50002 GLOBAL *\/ STATUS;' can be used to trigger global in both.
 * 5.0.2: NOT Parsing. Prior to 5.0.2, "NOT a BETWEEN b AND c" was parsed as "(NOT a) BETWEEN b AND ". Beginning in 5.0.2, it is parsed as "NOT (a BETWEEN b AND c)". The SQL mode "HIGH_NOT_PRECEDENCE" can be used to trigger the old mode. (http://dev.mysql.com/doc/refman/5.0/en/server-sql-mode.html#sqlmode_high_not_precedence).
 * 5.0.3: User defined functions must contain aux. symbols in order to run.
 * 5.0.3: The BIT Type. Prior to 5.0.3, the BIT type is a synonym for TINYINT(1). Beginning with 5.0.3, the BIT type accepts an additional argument (e.g. BIT(2)) that species the number of bits to use. (http://dev.mysql.com/doc/refman/5.0/en/bit-type.html)
 ** Due to performance, BIT is far better than TINYINT(1). Both, however, are supported in this class.
 * 5.0.3: Trailing spaces are not removed from VARCHAR and VARBINARY columns, but they were prior
 * 5.0.3: Decimal handling was changed (tables created prior to the change will maintain the old behaviour): (http://dev.mysql.com/doc/refman/5.0/en/precision-math-decimal-changes.html)
 ** Decimals are handled as binary; prior they were handled as strings.
 ** When handled as strings, the "-" sign could be replaced with any number, extending the range of DECIMAL(5,2) from the current (and standard) [-999.99,999.99] to [-999.99,9999.99], while preceeding zeros and +/- signs were maintained when stored.
 ** Additionally, prior to 5.0.3, the maximum number of digits is 264 (precise to ~15 depending on the host machine), from 5.0.3 to 5.0.5 it is 64 (precise to 64), and from 5.0.6 the maximum number of digits is 65 (precise to 65).
 ** Finally, while prior to this change both exact- and approximate-value literals were handled as double-precision floating point, now exact-value literals will be handled as decimal.
 * 5.0.6: Tables with DECIMAL columns created between 5.0.3 and 5.0.5 must be rebuilt.
 * 5.0.8: "DATETIME+0" yields YYYYMMDDHHMMSS.000000, but previously yielded YYYYMMDDHHMMSS.
 * 5.0.12: NOW() and SYSDATE() are no longer identical, with the latter be the time at script execution and the former at statement execution time (approximately).
 * 5.0.13: The GREATEST() and LEAST() functions return NULL when a passed parameter is NULL. Prior, they ignored NULL values.
 * 5.0.13: Substraction from an unsigned integer varies. Prior to 5.0.13, the bits of the subtracted value is used for the result (e.g. i-1, where i is TINYINT and 0, is the same as 0-2^64). In 5.0.13, it retains the bits of the original (e.g. it now would be 0-2^8). If comparing 
 * 5.0.15: The pad value for BINARY has changed from a space to \0, as has the handling of these. Using a BINARY(3) type with a value of 'a ' to illustrate: in the original, SELECT, DISTINCT, and ORDER BY operations remove all trailing spaces ('a'), while in the new version SELECT, DISTINCT, and ORDER BY maintain all additional null bytes ('a \0'). InnoDB still uses trailing spaces ('a  '), and did not remove the trailing spaces until 5.0.19 ('a').
 * 5.0.15: CHAR() returns a binary string instead of a character set. A "USING" may be used instead to specify a character set. For instance, SELECT CHAR() returns a VARBINARY but previously would have returned VARCHAR (similarly, CHAR(ORD('A')) is equvilent to 'a' prior to this change, but now would only be so if a latin character set is specified.).
 * 5.0.25: lc_time_names will affect the display of DATE_FORMAT(), DAYNAME(), and MONTHNAME().
 * 5.0.42: When DATE and DATETIME interact, DATE is now converted to DATETIME with 00:00:00. Prior to 5.0.42, DATETIME would instead loose its time portion. CAST() can be used to mimic the old behavior.
 * 5.0.50: Statesments containing "/*" without "*\/" are no longer accepted.
 * 5.1.0: table_cache -> table_open_cache
 * 5.1.0: "-", "*", "/", POW(), and EXP() now return NULL if an error is occured during floating-point operations. Previously, they may return "+INF", "-INF", or NaN.
 * 5.1.23: In stored routines, a cursor may no longer be used in SHOW and DESCRIBE statements.
 * 5.1.15: READ_ONLY 
 
 * Other incompatibilities that may be encountered:
 * Reserved Words Added in 5.0: http://dev.mysql.com/doc/mysqld-version-reference/en/mysqld-version-reference-reservedwords-5-0.html.
  ** This class puts everything in quotes to avoid this and related issues.
  ** Some upgrades may require rebuilding indexes. We are not concerned with these, but a script that automatically rebuilds indexes as part of databaseSQL.php would have its merits. It could then also detect version upgrades.
  ** Previously, TIMESTAMP(N) could specify a width of "N". It was ignored in 4.1, deprecated in 5.1, and removed in 5.5. Don't use it.
  ** UDFs should use a database qualifier to avoid issues with defined functions.
  ** The JOIN syntax was changed in MySQL 5.0.12. The new syntax will work with old versions, however (just not the other way around).
  ** Avoid equals comparison with floating point values.
  ** Timestamps are seriously weird in MySQL. Honestly, avoid them.
  *** 4.1 especially contains oddities: (http://dev.mysql.com/doc/refman/4.1/en/timestamp.html)
* 

 * Further Reading:
 ** http://dev.mysql.com/doc/refman/5.0/en/upgrading-from-previous-series.html */
 
class databaseSQL extends database {
  public $classVersion = 3;
  public $classProduct = 'fim';
  
  public $getVersion = false; // Whether or not to get the database version, adding overhead.
  
  public $version = 0;
  public $versionPrimary = 0;
  public $versionSeconday = 0;
  public $versionTertiary = 0;
  public $versionString = '0.0.0';
  public $supportedLanguages = array('mysql', 'mysqli');
  public $storeTypes = array('memory', 'general', 'innodb');
  public $queryLog = array();
  public $mode = 'SQL';
  public $language = '';
  
  protected $dbLink = false;

  /*********************************************************
  ************************ START **************************
  ******************* General Functions *******************
  *********************************************************/
  
  /**
   * Calls a database function, such as mysql_connect or mysql_query, using lookup tables
   *
   * @return void
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  private function functionMap($operation) {
    $args = func_get_args();

    switch ($this->language) {
      case 'mysql':
      switch ($operation) {
        case 'connect':
          $function = mysql_connect("$args[1]:$args[2]", $args[3], $args[4]);
          
          if ($this->getVersion) $this->version = $this->setDatabaseVersion(mysql_get_server_info($function));

          return $function;
        break;

        case 'error':    return mysql_error(isset($this->dbLink) ? $this->dbLink : null);                                             break;
        case 'close':    $function = mysql_close($this->dbLink); unset($this->dbLink); return $function;                              break;
        case 'selectdb': return mysql_select_db($args[1], $this->dbLink);                                                             break;
        case 'escape':   return mysql_real_escape_string($args[1], $this->dbLink);                                                    break;
        case 'query':    return mysql_query($args[1], $this->dbLink);                                                                 break;
        case 'insertId': return mysql_insert_id($this->dbLink);                                                                       break;
        default:         $this->triggerError("[Function Map] Unrecognised Operation", array('operation' => $operation), 'validation'); break;
      }
      break;


      case 'mysqli':
      switch ($operation) {
        case 'connect':
          $function = mysqli_connect($args[1], $args[3], $args[4], ($args[5] ? $args[5] : null), (int) $args[2]);
          $this->version = mysqli_get_server_info($function);

          return $function;
        break;

        case 'error':
          if (isset($this->dbLink)) return mysqli_error($this->dbLink);
          else                      return mysqli_connect_error();
        break;

        case 'selectdb': return mysqli_select_db($this->dbLink, $args[1]);                                                            break;
        case 'close':    return mysqli_close($this->dbLink);                                                                          break;
        case 'escape':   return mysqli_real_escape_string($this->dbLink, $args[1]);                                                   break;
        case 'query':    return mysqli_query($this->dbLink, $args[1]);                                                                break;
        case 'insertId': return mysqli_insert_id($this->dbLink);                                                                      break;
        default:         $this->triggerError("[Function Map] Unrecognised Operation", array('operation' => $operation), 'validation'); break;
      }
      break;


      case 'postgresql':
      switch ($operation) {
        case 'connect':  return pg_connect("host=$args[1] port=$args[2] username=$args[3] password=$args[4] dbname=$args[5]");       break;
        case 'error':    return pg_last_error($this->dbLink);                                                                        break;
        case 'close':    return pg_close($this->dbLink);                                                                             break;
        case 'escape':   return pg_escape_string($this->dbLink, $args[1]);                                                           break;
        case 'query':    return pg_query($this->dbLink, $args[1]);                                                                   break;
        case 'insertId': /* TODO */                                                                                                  break;
        default:        $this->triggerError("[Function Map] Unrecognised Operation", array('operation' => $operation), 'validation'); break;
      }
      break;
    }
  }
  
  
  
  /** Format a value to represent the specified type in an SQL query.
   *
   * @param int|string value - The value to format.
   * @param string type - The type to format as, either "search", "string", "integer", or "column".
   * @return int|string - Value, formatted as specified.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  private function formatValue($type) {
    $values = func_get_args();
    
    switch ($type) {
      case 'search':    return $this->stringQuoteStart . $this->stringFuzzy . $this->escape($values[1], 'search') . $this->stringFuzzy . $this->stringQuoteEnd; break;
      case 'string':    return $this->stringQuoteStart . $this->escape($values[1], 'string') . $this->stringQuoteEnd;                                           break;
      case 'integer':   return $this->intQuoteStart . (int) $this->escape($values[1], 'integer') . $this->intQuoteEnd;                                          break;
      case 'timestamp': return $this->timestampQuoteStart . (int) $this->escape($values[1], 'timestamp') . $this->timestampQuoteEnd;                            break;
      case 'column':    return $this->columnQuoteStart . $this->escape($values[1], 'column') . $this->columnQuoteEnd;                                           break;
      case 'columnA':   return $this->columnAliasQuoteStart . $this->escape($values[1], 'columnA') . $this->columnAliasQuoteEnd;                                break;
      case 'table':     return $this->tableQuoteStart . $this->escape($values[1], 'table') . $this->tableQuoteEnd;                                              break;
      case 'tableA':    return $this->tableAliasQuoteStart . $this->escape($values[1], 'tableA') . $this->tableAliasQuoteEnd;                                   break;
      case 'database':  return $this->databaseQuoteStart . $this->escape($values[1], 'database') . $this->databaseQuoteEnd;                                     break;
      case 'index':     return $this->indexQuoteStart . $this->escape($values[1], 'index') . $this->indexQuoteEnd;                                              break;
      case 'array':
        foreach ($values[1] AS &$item) {
          if     (is_string($item)) $item = $this->formatValue('string', $item); // Format as a string.
          elseif (is_int($item))    continue; // Do nothing.
          else   $this->triggerError('Improper item type in array.', $values[1], 'validation');
        }

        return $this->arrayQuoteStart . implode($this->arraySeperator, $values[1]) . $this->arrayQuoteEnd; // Combine as list.
      break;

      case 'tableColumn':   return $this->formatValue('table', $values[1]) . $this->tableColumnDivider . $this->formatValue('column', $values[2]);     break;
      case 'databaseTable': return $this->formatValue('database', $values[1]) . $this->databaseTableDivider . $this->formatValue('table', $values[2]); break;
      
      case 'tableColumnAlias': return $this->formatValue('table', $values[1]) . $this->tableColumnDivider . $this->formatValue('column', $values[2]) . $this->columnAliasDivider . $this->formatValue('columnA', $values[3]); break;
      case 'tableAlias' : return $this->formatValue('table', $values[1]) . $this->tableAliasDivider . $this->formatValue('tableA', $values[2]);     break;
    }
  }
  
  
  
  /** Formats two columns or table names such that one is an alias.
   *
   * @param string value - The value (column name/table name).
   *
   * @internal Needless to say, this is quite the simple function. However, I feel that the syntax merits it, as there are certainly other ways an "AS" could be structure. (Most wouldn't comply with SQL, but strictly speaking I would like this class to work with slight modifications of SQL as well, if any exist.)
   *
   * @param string alias - The alias.
   * @return string - The formatted SQL string.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
/*  private function formatAlias($value, $alias, $type) {
    switch ($type) {
      case 'column': case 'table': return "$value AS $alias"; break;
    }
  }*/
  
  
  
  private function setDatabaseVersion($versionString) {
    $versionString = (string) $versionString;
    $this->versionString = $versionString;
    $strippedVersion = '';
    
    // Get the version without any extra crap (e.g. "5.0.0.0~ubuntuyaypartytimeohohohoh").
    for ($i = 0; $i < strlen($versionString); $i++) {
      if (in_array($versionString[$i], array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.'), true)) $strippedVersion .= $versionString[$i];
      else break;
    }

    // Divide the decimal versions into an array (e.g. 5.0.1 becomes [0] => 5, [1] => 0, [2] => 1) and set the first three as properties.
    $strippedVersionParts = explode('.', $strippedVersion);
    
    $this->versionPrimary = $strippedVersionParts[0];
    $this->versionSeconday = $strippedVersionParts[1];
    $this->versionTertiary = $strippedVersionParts[2];
    

    // Compatibility check. We're really not sure how true any of this, and we have no reason to support older versions, but meh.
    switch ($driver) {
      case 'mysql': case 'mysqli':
      if ($strippedVersionParts[0] <= 4) { // MySQL 4 is a no-go.
        die('You have attempted to connect to a MySQL version 4 database. MySQL 5.0.5+ is required for FreezeMessenger.');
      }
      elseif ($strippedVersionParts[0] == 5 && $strippedVersionParts[1] == 0 && $strippedVersionParts[2] <= 4) { // MySQL 5.0.0-5.0.4 is also a no-go (we require the BIT type, even though in theory we could work without it)
        die('You have attempted to connect to an incompatible version of a MySQL version 5 database (MySQL 5.0.0-5.0.4). MySQL 5.0.5+ is required for FreezeMessenger.');
      }
      break;
    }
  }


  
  public function connect($host, $port, $user, $password, $database, $driver, $tablePrefix) {
    $this->setLanguage($driver);
    $this->sqlPrefix = $tablePrefix;

    if (!$link = $this->functionMap('connect', $host, $port, $user, $password, $database)) { // Make the connection.
      $this->triggerError('Could Not Connect', array( // Note: we do not include "password" in the error data.
        'host' => $host,
        'port' => $port,
        'user' => $user,
        'database' => $database
      ), 'connection');

      return false;
    }
    else {
      $this->dbLink = $link; // Set the object property "dbLink" to the database connection resource. It will be used with most other queries that can accept this parameter.
    }

    if (!$this->activeDatabase && $database) { // Some drivers will require this.
      if (!$this->selectDatabase($database)) { // Error will be issued in selectDatabase.
        return false;
      }
    }

    return true;
  }
  
  
  
  public function close() {
    return $this->functionMap('close');
  }
  
    
    
  /**
   * Set Language / Database Driver
   *
   * @param string language
   * @return void
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  private function setLanguage($language) {
    $this->language = $language;

    switch ($this->language) {
      case 'mysql':
      case 'mysqli':
      $this->tableQuoteStart = '`';      $this->tableQuoteEnd = '`';    $this->tableAliasQuoteStart = '`';    $this->tableAliasQuoteEnd = '`';
      $this->columnQuoteStart = '`';     $this->columnQuoteEnd = '`';   $this->columnAliasQuoteStart = '`';   $this->columnAliasQuoteEnd = '`';
      $this->databaseQuoteStart = '`';   $this->databaseQuoteEnd = '`'; $this->databaseAliasQuoteStart = '`'; $this->databaseAliasQuoteEnd = '`';
      $this->stringQuoteStart = '"';     $this->stringQuoteEnd = '"';   $this->emptyString = '""';            $this->stringFuzzy = '%';
      $this->arrayQuoteStart = '(';      $this->arrayQuoteEnd = ')';    $this->arraySeperator = ', ';
      $this->intQuoteStart = '';         $this->intQuoteEnd = '';
      $this->tableColumnDivider = '.';   $this->databaseTableDivider = '.';
      $this->sortOrderAsc = 'ASC';       $this->sortOrderDesc = 'DESC';
      $this->tableAliasDivider = ' AS '; $this->columnAliasDivider = ' AS ';

      $this->tableTypes = array(
        'general' => 'InnoDB',
        'memory' => 'MEMORY',
      );
      break;

      case 'postgresql':
      $this->tableQuoteStart = '"';    $this->tableQuoteEnd = '"';    $this->tableAliasQuoteStart = '"';    $this->tableAliasQuoteEnd = '"';
      $this->columnQuoteStart = '"';   $this->columnQuoteEnd = '"';   $this->columnAliasQuoteStart = '"';   $this->columnAliasQuoteEnd = '"';
      $this->databaseQuoteStart = '"'; $this->databaseQuoteEnd = '"'; $this->databaseAliasQuoteStart = '"'; $this->databaseAliasQuoteEnd = '"';
      $this->stringQuoteStart = '"';   $this->stringQuoteEnd = '"';   $this->emptyString = '""';            $this->stringFuzzy = '%';
      $this->arrayQuoteStart = '(';    $this->arrayQuoteEnd = ')';    $this->arraySeperator = ', ';
      $this->intQuoteStart = '';       $this->intQuoteEnd = '';
      $this->tableColumnDivider = '.'; $this->databaseTableDivider = '.';
      $this->sortOrderAsc = 'ASC';     $this->sortOrderDesc = 'DESC';
      $this->tableAliasDivider = ' AS '; $this->columnAliasDivider = ' AS ';
      break;
    }

    switch ($this->language) {
      case 'mysql':
      case 'mysqli':
      case 'postgresql':
      $this->comparisonTypes = array(
        'e' => '=',  '!e' => '!=', 'in' => 'IN',  '!in' => 'NOT IN',
        'lt' => '<', 'gt' => '>',  'lte' => '<=', 'gte' => '>=',
        'regex' => 'REGEXP',
        'search' => 'LIKE',
        'bAnd' => '&',
      );

      $this->concatTypes = array(
        'both' => ' AND ', 'either' => ' OR ',
      );

      $this->keyTypeConstants = array(
        'primary' => 'PRIMARY KEY',
        'unique' => 'UNIQUE KEY',
        'index' => 'KEY',
      );

      $this->defaultPhrases = array(
        '__TIME__' => 'CURRENT_TIMESTAMP',
      );

      $this->columnIntLimits = array(
        1 => 'TINYINT',   2 => 'TINYINT',   3 => 'SMALLINT',  4 => 'SMALLINT', 5 => 'MEDIUMINT',
        6 => 'MEDIUMINT', 7 => 'MEDIUMINT', 8 => 'INT',       9 => 'INT',      0 => 'BIGINT'
      );

      $this->columnStringPermLimits = array(
        1 => 'CHAR', 255 => 'VARCHAR', 1000 => 'TEXT', 8191 => 'MEDIUMTEXT', 2097151 => 'LONGTEXT'
      );

      $this->columnStringTempLimits = array(
        255 => 'CHAR', 65535 => 'VARCHAR'
      );

      $this->columnStringNoLength = array(
        'MEDIUMTEXT', 'LONGTEXT'
      );

      $this->columnBitLimits = array(
        0 => 'TINYINT UNSIGNED',    8 => 'TINYINT UNSIGNED',  16 => 'SMALLINT UNSIGNED',
        24 => 'MEDIUMINT UNSIGNED', 32 => 'INTEGER UNSIGNED', 64 => 'LONGINT UNSIGNED',
      );

      $this->globFindArray = array('*', '?');
      $this->globReplaceArray = array('%', '_');
      
      $this->boolValues = array(
        true => 1, false => 0,
      );
      
      $this->columnTypeConstants = array(
        'bool' => 'TINYINT(1) UNSIGNED',
        'time' => 'INTEGER UNSIGNED',
      );
      break;
    }
  }
  
  

  /**
   * Returns a properly escaped string for raw queries.
   *
   * @param string int|string - Value to escape.
   * @param string context - The value type, in-case the escape method varies based on it.
   * @return string
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
   
  protected function escape($string, $context = 'string') {
    if ($context === 'search') {
      $string = addcslashes($string, '%_\\'); // TODO: Verify
    }

    return $this->functionMap('escape', $string, $context); // Return the escaped string.
  }


  
  /**
   * Sends a raw, unmodified query string to the database server.
   * The query may be logged if it takes a certain amount of time to execute successfully.
   *
   * @param string $query - The raw query to execute.
   * @return resource|bool - The database resource returned by the query, or false on failure.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  protected function rawQuery($query, $suppressErrors = false) {
    $this->sourceQuery = $query;

    if ($queryData = $this->functionMap('query', $query)) {
      $this->queryCounter++;

      if ($queryData === true) return true; // Insert, Update, Delete, etc.
      else return new databaseResult($queryData, $query, $this->language); // Select, etc.
    }
    else {
      $this->triggerError('Database Error', array(
        'query' => $query,
        'error' => $this->functionMap('error')
      ), 'syntax', $suppressErrors); // The query could not complete.

      return false;
    }
  }
  
  

  /**
   * Add the text of a query to the log. This should normally only be called by rawQuery(), but is left protected since other purposes could exist by design.
   *
   * @return string - The query text of the last query executed.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */

  protected function newQuery($queryText) {
    $this->queryLog[] = $queryText;
  }
  
  
  
  /**
   * Get the text of the last query executed.
   *
   * @return string - The query text of the last query executed.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function getLastQuery() {
    return end($this->queryLog);
  }
  
  
  
  /**
   * Clears the query log.
   *
   * @return string - The query text of the last query executed.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  public function clearQueries() {
    $this->queryLog = array();
  }
  
  /*********************************************************
  ************************* END ***************************
  ******************* General Functions *******************
  *********************************************************/

  
  
  /*********************************************************
  ************************ START **************************
  ****************** Database Functions *******************
  *********************************************************/
  
  public function selectDatabase($database) {
    $error = false;
    
    if ($this->functionMap('selectdb', $database)) { // Select the database.      
      if ($this->language == 'mysql' || $this->language == 'mysqli') {
        if (!$this->rawQuery('SET NAMES "utf8"', true)) { // Sets the database encoding to utf8 (unicode).
          $error = 'SET NAMES Query Failed';
        }
      }
    }
    else {
      $error = 'Failed to Select Database';
    }
    
    if ($error) {
      $this->triggerError($error, array(
        'database' => $database,
        'query' => $this->getLastQuery(),
        'sqlError' => $this->getLastError()
      ), 'function');
      return false;
    }
    else {
      $this->activeDatabase = $database;
      return true;
    }
  }
  
  
  
  public function createDatabase($database) {
    return $this->rawQuery('CREATE DATABASE IF NOT EXISTS ' . $this->formatValue('database', $database));
  }
  
  /*********************************************************
  ************************* END ***************************
  ****************** Database Functions *******************
  *********************************************************/
  
  
  
  /*********************************************************
  ************************ START **************************
  ******************* Table Functions *********************
  *********************************************************/

  public function createTable($tableName, $tableComment, $engine, $tableColumns, $tableIndexes) {
    if (isset($this->tableTypes[$engine])) {
      $engineName = $this->tableTypes[$engine];
    }
    else {
      $this->triggerError("Unrecognised Table Engine", array(
        'tableName' => $tableName,
        'engine' => $engine
      ), 'validation');
    }

    $tableProperties = '';
    
    foreach ($tableColumns AS $columnName => $column) {
      $typePiece = '';

      switch ($column['type']) {
        case 'int':
        if (isset($this->columnIntLimits[$column['maxlen']])) {
          if (in_array($type, $this->columnStringNoLength)) $typePiece = $this->columnIntLimits[$column['maxlen']];
          else $typePiece = $this->columnIntLimits[$column['maxlen']] . '(' . (int) $column['maxlen'] . ')';
        }
        else {
          $typePiece = $this->columnIntLimits[0];
        }

        if (isset($column['autoincrement']) && $column['autoincrement']) {
          $typePiece .= ' AUTO_INCREMENT'; // Ya know, that thing where it sets itself.
          $tableProperties .= ' AUTO_INCREMENT = ' . (int) $column['autoincrement'];
        }
        break;

        case 'string':
        if ($column['restrict']) {
          $restrictValues = array();

          foreach ((array) $column['restrict'] AS $value) $restrictValues[] = '"' . $this->escape($value) . '"';

          $typePiece = 'ENUM(' . implode(',',$restrictValues) . ')';
        }
        else {
          if ($engine === 'memory')    $this->columnStringLimits = $this->columnStringTempLimits;
          else                         $this->columnStringLimits = $this->columnStringPermLimits;

          $typePiece = '';

          foreach ($this->columnStringLimits AS $length => $type) {
            if ($column['maxlen'] <= $length) {
              if (in_array($type, $this->columnStringNoLength)) $typePiece = $type;
              else $typePiece = $type . '(' . $column['maxlen'] . ')';

              break;
            }
          }

          if (!$typePiece) {
            $typePiece = $this->columnStringNoLength[0];
          }
        }

        $typePiece .= ' CHARACTER SET utf8 COLLATE utf8_bin';
        break;

        case 'bitfield':
        if ($this->nativeBitfield) {
        
        }
        else {
          if (!isset($column['bits'])) {
            $typePiece = 'TINYINT UNSIGNED'; // Sane default
          }
          else {
            if ($column['bits'] <= 8)      $typePiece = 'TINYINT UNSIGNED';
            elseif ($column['bits'] <= 16) $typePiece = 'SMALLINT UNSIGNED';
            elseif ($column['bits'] <= 24) $typePiece = 'MEDIUMINT UNSIGNED';
            elseif ($column['bits'] <= 32) $typePiece = 'INTEGER UNSIGNED';
            else                           $typePiece = 'LONGINT UNSIGNED';
          }
        }
        break;

        case 'time':
        $typePiece = 'INTEGER UNSIGNED'; // Note: replace with LONGINT to avoid the Epoch issues in 2038 (...I'll do it in FIM5 or so). For now, it's more optimized. Also, since its UNSIGNED, we actually have more until 2106 or something like that.
        break;

        case 'bool':
        $typePiece = 'TINYINT(1) UNSIGNED';
        break;

        default:
        $this->triggerError("Unrecognised Column Type", array(
          'tableName' => $tableName,
          'columnName' => $columnName,
          'columnType' => $column['type'],
        ), 'validation');
        break;
      }


      if ($column['default']) {
        if (isset($this->defaultPhrases[$column['default']])) {
          $typePiece .= ' DEFAULT ' . $this->defaultPhrases[$column['default']];
        }
        else {
          $typePiece .= ' DEFAULT "' . $this->escape($column['default']) . '"';
        }
      }

      $columns[] = $this->formatValue('column', $columnName) . $typePiece . ' NOT NULL COMMENT "' . $this->escape($column['comment']) . '"';
    }



    foreach ($tableIndexes AS $indexName => $index) {
      if (isset($this->keyTypeConstants[$index['type']])) {
        $typePiece = $this->keyTypeConstants[$index['type']];
      }
      else {
        $this->triggerError("Unrecognised Index Type", array(
          'tableName' => $tableName,
          'indexName' => $indexName,
          'indexType' => $index['type'],
        ), 'validation');
      }


      if (strpos($indexName, ',') !== false) {
        $indexCols = explode(',', $indexName);

        foreach ($indexCols AS &$indexCol) $indexCol = $this->formatValue('column', $indexCol);

        $indexName = implode(',', $indexCols);
      }
      else {
        $this->formatValue('index', $indexName);
      }


      $indexes[] = "{$typePiece} ({$indexName})";
    }

    return $this->rawQuery('CREATE TABLE IF NOT EXISTS ' . $this->formatValue('table', $tableName) . ' (
' . implode(",\n  ", $columns) . ',
' . implode(",\n  ", $indexes) . '
) ENGINE="' . $this->escape($engineName) . '" COMMENT="' . $this->escape($tableComment) . '" DEFAULT CHARSET="utf8"' . $tableProperties);
  }
  
  
  
  public function deleteTable($tableName) {
    return $this->rawQuery('DROP TABLE ' . $this->formatValue('table', $tableName));
  }
  
  
  
  public function renameTable($oldName, $newName) {
    return $this->rawQuery('RENAME TABLE ' . $this->formatValue('table', $oldName) . ' TO ' . $this->formatValue('table', $newName));
  }
  
  
  
  public function getTablesAsArray() {
    switch ($this->language) {
      case 'mysql': case 'mysqli': case 'postgresql':
      $tables = $this->rawQuery('SELECT * FROM ' . $this->formatValue('databaseTable', 'INFORMATION_SCHEMA', 'TABLES') . ' WHERE TABLE_SCHEMA = "' . $this->escape($this->activeDatabase) . '"');
      $tables = $tables->getAsArray('TABLE_NAME');
      $tables = array_keys($tables);
      break;
    }
    
    return $tables;
  }
  
  /*********************************************************
  ************************* END ***************************
  ******************* Table Functions *********************
  *********************************************************/
  
  
  
  /*********************************************************
  ************************ START **************************
  ******************** Row Functions **********************
  *********************************************************/  
  
  public function select($columns, $conditionArray = false, $sort = false, $limit = false) {
      /* Define Variables */
    $finalQuery = array(
      'columns' => array(),
      'tables' => array(),
      'where' => '',
      'sort' => array(),
      'group' => '',
      'limit' => 0
    );
    $reverseAlias = array();


    /* Process Columns (Must be Array) */
    if (is_array($columns)) {
      if (count($columns) > 0) {
        foreach ($columns AS $tableName => $tableCols) {
          if (strlen($tableName) > 0) { // If the tableName is defined...
            if (strstr($tableName, ' ') !== false) { // A space can be used to create a table alias, which is sometimes required for different queries.
              $tableParts = explode(' ', $tableName);

              $finalQuery['tables'][] = $this->formatValue('tableAlias', $tableParts[0], $tableParts[1]); // Identify the table as [tableName] AS [tableAlias]

              $tableName = $tableParts[1];
            }
            else {
              $finalQuery['tables'][] = $this->formatValue('table', $tableName); // Identify the table as [tableName]
            }

            if (is_array($tableCols)) { // Table columns have been defined with an array, e.g. ["a", "b", "c"]
              foreach($tableCols AS $colName => $colAlias) {
                if (strlen($colName) > 0) {
                  if (strstr($colName,' ') !== false) { // A space can be used to create identical columns in different contexts, which is sometimes required for different queries.
                    $colParts = explode(' ', $colName);
                    $colName = $colParts[0];
                  }

                  if (is_array($colAlias)) { // Used for advance structures and function calls.
                  
                    throw new Exception('TODO');
                    
                    if (isset($colAlias['context'])) {
                      throw new Exception('Deprecated context.'); // TODO
                    }
                    
                    $finalQuery['columns'][] = $this->formatValue('tableColumnAlias', $tableName, $colName, $colAlias);
                    $reverseAlias[$colAlias] = $this->formatValue('tableColumn', $tableName, $colName);
                  }
                  else {
                    $finalQuery['columns'][] = $this->formatValue('tableColumnAlias', $tableName, $colName, $colAlias);
                    $reverseAlias[$colAlias] = $this->formatValue('tableColumn', $tableName, $colName);
                  }
                }
                else {
                  $this->triggerError('Invalid Select Array (Empty Column Name)', array(
                    'tableName' => $tableName,
                    'columnName' => $colName,
                  ), 'validation');
                }
              }
            }
            elseif (is_string($tableCols)) { // Table columns have been defined with a string list, e.g. "a,b,c"
              $colParts = explode(',', $tableCols); // Split the list into an array, delimited by commas

              foreach ($colParts AS $colPart) { // Run through each list item
                $colPart = trim($colPart); // Remove outside whitespace from the item

                if (strpos($colPart, ' ') !== false) { // If a space is within the part, then the part is formatted as "columnName columnAlias"
                  $colPartParts = explode(' ', $colPart); // Divide the piece

                  $colPartName = $colPartParts[0]; // Set the name equal to the first part of the piece
                  $colPartAlias = $colPartParts[1]; // Set the alias equal to the second part of the piece
                }
                else { // Otherwise, the column name and alias are one in the same.
                  $colPartName = $colPart; // Set the name and alias equal to the piece
                  $colPartAlias = $colPart;
                }

                //$finalQuery['columns'][] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd . $this->tableColumnDivider . $this->columnQuoteStart . $columnPartName . $this->columnQuoteStart . ' AS ' . $this->columnAliasQuoteEnd . $columnPartAlias . $this->columnAliasQuoteStart;
                // $reverseAlias[$columnPartAlias] = $this->tableQuoteStart . $tableName . $this->tableQuoteEnd . $this->tableColumnDivider . $this->columnQuoteStart . $columnPartName . $this->columnQuoteStart;
                
                $finalQuery['columns'][] = $this->formatValue('tableColumnAlias', $tableName, $colPartName, $colPartAlias);
                $reverseAlias[$colPartAlias] = $this->formatValue('tableColumn', $tableName, $colPartName);
              }
            }
          }
          else {
            $this->triggerError('Invalid Select Array (Empty Table Name)', array(
              'tableName' => $tableName,
            ), 'validation');
          }
        }
      }
      else {
        $this->triggerError('Invalid Select Array (Columns Array Empty)', array(), 'validation');
      }
    }
    else {
      $this->triggerError('Invalid Select Array (Columns Not an Array)', array(), 'validation');
    }



    /* Process Conditions (Must be Array) */
    if ($conditionArray !== false) {
      if (is_array($conditionArray)) {
        if (count($conditionArray) > 0) {
          $finalQuery['where'] = $this->recurseBothEither($conditionArray, $reverseAlias);
        }
      }
    }



    /* Process Sorting (Must be Array)
     * TODO: Combine the array and string routines to be more effective. */
    if ($sort !== false) {
      if (is_array($sort)) {
        if (count($sort) > 0) {
          foreach ($sort AS $sortColumn => $direction) {
            if (isset($reverseAlias[$sortColumn])) {
              switch (strtolower($direction)) {
                case 'asc': $directionSym = $this->sortOrderAsc; break;
                case 'desc': $directionSym = $this->sortOrderDesc; break;
                default: $directionSym = $this->sortOrderAsc; break;
              }

              $finalQuery['sort'][] = $reverseAlias[$sortColumn] . " $directionSym";
            }
            else {
              $this->triggerError('Unrecognised Sort Column', array(
                'sortColumn' => $sortColumn,
              ), 'validation');
            }
          }
        }
      }
      elseif (is_string($sort)) {
        $sortParts = explode(',', $sort); // Split the list into an array, delimited by commas

        foreach ($sortParts AS $sortPart) { // Run through each list item
          $sortPart = trim($sortPart); // Remove outside whitespace from the item

          if (strpos($sortPart,' ') !== false) { // If a space is within the part, then the part is formatted as "columnName direction"
            $sortPartParts = explode(' ',$sortPart); // Divide the piece

            $sortColumn = $sortPartParts[0]; // Set the name equal to the first part of the piece
            switch (strtolower($sortPartParts[1])) {
              case 'asc':  $directionSym = $this->sortOrderAsc;  break;
              case 'desc': $directionSym = $this->sortOrderDesc; break;
              default:     $directionSym = $this->sortOrderAsc;  break;
            }
          }
          else { // Otherwise, we assume asscending
            $sortColumn = $sortPart; // Set the name equal to the sort part.
            $directionSym = $this->sortOrderAsc; // Set the alias equal to the default, ascending.
          }

          $finalQuery['sort'][] = $reverseAlias[$sortColumn] . " $directionSym";
        }
      }

      $finalQuery['sort'] = implode(', ', $finalQuery['sort']);
    }



    /* Process Limit (Must be Integer) */
    if ($limit !== false) {
      if (is_int($limit)) {
        $finalQuery['limit'] = (int) $limit;
      }
    }



    /* Generate Final Query */
    $finalQueryText = 'SELECT
  ' . implode(',
  ', $finalQuery['columns']) . '
FROM
  ' . implode(', ', $finalQuery['tables']) . ($finalQuery['where'] ? '
WHERE
  ' . $finalQuery['where'] : '') . ($finalQuery['sort'] ? '
ORDER BY
  ' . $finalQuery['sort'] : '') . ($finalQuery['limit'] ? '
LIMIT
  ' . $finalQuery['limit'] : '');

    /* And Run the Query */
    return $this->rawQuery($finalQueryText);
  }
  
  
  
  /**
   * Recurses over a specified "where" array, returning a valid where clause.
   *
   * @param array $conditionArray - The conditions to transform into proper SQL.
   * @param array $reverseAlias - An array corrosponding to column aliases and their database counterparts.
   * @param int $d - The level of recursion.
   *
   * @return string
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
   */
  private function recurseBothEither($conditionArray, $reverseAlias, $d = 0) {
    $i = 0;
    $h = 0;
    $whereText = array();

//    if ($d == 1) {var_dump($conditionArray); die();}

    // $type is either "both", "either", or "neither". $cond is an array of arguments.
    foreach ($conditionArray AS $type => $cond) {
      // First, make sure that $cond isn't empty. Pretty simple.
      if (is_array($cond) && count($cond) > 0) {
        // $key is usually a column, $value is a formatted value for the select() function.
        foreach ($cond AS $key => $value) {
          $i++;

          if ($key === 'both' || $key === 'either' || $key === 'neither') {
            $sideTextFull[$i] = $this->recurseBothEither($cond, $reverseAlias, 1);
          }
          else {
            if (strstr($key, ' ') !== false) list($key) = explode(' ', $key); // A space can be used to reference the same key twice in different contexts. It's basically a hack, but it's better than using further arrays.

            /* Value is currently stored as:
             * array(TYPE, VALUE, COMPARISON)
             *
             * Note: We do not want to include quotes/etc. in VALUE yet, because these theoretically could vary based on the comparison type. */
            $sideTextFull[$i] = '';      

            $sideText['left'] = $reverseAlias[($this->startsWith($key, '!') ? substr($key, 1) : $key)]; // Get the column definition that corresponds with the named column. "!column" signifies negation.
            $symbol = $this->comparisonTypes[$value[2]];
            
            if ($value[0] === 'column') $sideText['right'] = $reverseAlias[$value[1]]; // The value is a column, and should be returned as a reverseAlias. (Note that reverseAlias should have already called formatValue)
            else $sideText['right'] = $this->formatValue(($value[2] === 'search' ? $value[2] : $value[0]), $value[1]); // The value is a data type, and should be processed as such.
            
            if ((strlen($sideText['left']) > 0) && (strlen($sideText['right']) > 0)) {
              $sideTextFull[$i] = ($this->startsWith($key, '!') ? '!' : '') . "({$sideText['left']} {$symbol} {$sideText['right']})";
            }
            else {//var_dump($reverseAlias); echo $key;  var_dump($value); var_dump($sideText); die();
              $sideTextFull[$i] = "FALSE"; // Instead of throwing an exception, which should be handled above, instead simply cancel the query in the cleanest way possible. Here, it's specifying "FALSE" in the where clause to prevent any results from being returned.

              $this->triggerError('Query Nullified', array(), 'validation'); // Dev, basically. TODO.
            }
          }
        }

        if (isset($this->concatTypes[$type])) {
          $condSymbol = $this->concatTypes[$type];
        }
        else {
          $this->triggerError('Unrecognised Concatenation Operator', array(
            'operator' => $type,
          ), 'validation');
        }

        $whereText[$h] = implode($condSymbol, $sideTextFull);
      }
    }


    // Combine the query array if multiple entries exist, or just get the first entry.
    if (count($whereText) === 0) return false;
    elseif (count($whereText) === 1) $whereText = $whereText[0]; // Get the query string from the first (and only) index.
    else $whereText = implode($this->concatTypes['both'], $whereText);


    return "($whereText)"; // Return condition string. We wrap parens around to support multiple levels of conditions/recursion.
  }



  /**
   * Divides a multidimensional array into three seperate two-dimensional arrays, and performs some additional processing as defined in the passed array. It is used by the insert(), update(), and delete() functions.
   *
   * @param string $array - The source array
   *
   * @return array - An array containing three seperate arrays.
   * @author Joseph Todd Parsons <josephtparsons@gmail.com>
  */
  private function splitArray($array) {
    $columns = array(); // Initialize arrays
    $values = array(); // Initialize arrays

    foreach($array AS $column => $data) { // Run through each element of the $dataArray, adding escaped columns and values to the above arrays.

      $columns[] = $this->formatValue('column', $column);
      
      switch (gettype($data)) {
        case 'integer':// Safe integer - leave it as-is.
        $context[] = 'e'; // Equals
        $values[] = $data;
        break;
        
        case 'boolean':
        $context[] = 'e'; // Equals

        if ($data === true) $values[] = 1;
        elseif ($data === false) $values[] = 0;
        break;
        
        case 'NULL': // Null data, simply make it empty.
        $context[] = 'e';

        $values[] = $this->emptyString;
        break;
        
        case 'array': // This allows for some more advanced datastructures; specifically, we use it here to define metadata that prevents $this->escape.
        if (isset($data['context'])) {
          throw new Exception('Deprecated context'); // TODO
        }
        
        if (!isset($data['type'])) $data['type'] = 'string';

        switch($data['type']) {
          case 'equation':        $values[] = preg_replace('/\$([a-zA-Z\_]+)/', '\\1', $data['value']); break;
          case 'int':             $values[] = $this->formatValue('integer', $data['value']);            break;
          case 'string': default: $values[] = $this->formatValue('string', $data['value']);             break;
        }

        if (isset($data['cond'])) $context[] = $data['cond'];
        else                      $context[] = 'e';
        break;
      
        case 'string': // Simple Ol' String
        $values[] = $this->formatValue('string', $data);
        $context[] = 'e'; // Equals
        break;
        
        default:
        throw new Exception('Unrecognised data type.');
        break;
        
      }
    }
//    print_r($columns);
    return array($columns, $values, $context);
  }
  
  
  
  public function insert($table, $dataArray, $updateArray = false) {
    list($columns, $values) = $this->splitArray($dataArray);
    
    $columns = implode(',', $columns); // Convert the column array into to a string.
    $values = implode(',', $values); // Convert the data array into a string.

    $query = "INSERT INTO $table ($columns) VALUES ($values)";

    if ($updateArray) { // This is used for an ON DUPLICATE KEY request.
      list($columns, $values) = $this->splitArray($updateArray);

      for ($i = 0; $i < count($columns); $i++) {
        $update[] = $columns[$i] . ' = ' . $values[$i];
      }

      $update = implode($update, ', ');

      $query = "$query ON DUPLICATE KEY UPDATE $update";
    }

    if ($queryData = $this->rawQuery($query)) {
      $this->insertId = $this->functionMap('insertId');

      return $queryData;
    }
    else {
      return false;
    }
  }
  
  
  
  public function delete($tableName, $conditionArray = false) {
    if ($conditionArray === false) {
      $delete = 'TRUE';
    }
    else {
      list($columns, $values, $conditions) = $this->splitArray($conditionArray);

      for ($i = 0; $i < count($columns); $i++) {
        if (!$conditions[$i]) {
          $csym = $this->comparisonTypes['e'];
        }
        elseif (isset($this->comparisonTypes[$conditions[$i]])) {
          $csym = $this->comparisonTypes[$conditions[$i]];
        }
        else {
          $this->triggerError("[Update Table] Unrecognised Comparison Type", array(
            'tableName' => $tableName,
            'comparisonType' => $conditions[$i],
          ), 'validation');
        }

        $delete[] = $columns[$i] . $csym . $values[$i];
      }

      $delete = implode($delete, $this->concatTypes['both']);
    }

    return $this->rawQuery("DELETE FROM $tableName WHERE $delete");
  }
  
  
  
  public function update($tableName, $dataArray, $conditionArray = false) {
    list($columns, $values) = $this->splitArray($dataArray);
    
    for ($i = 0; $i < count($columns); $i++) {
      $update[] = $columns[$i] . ' = ' . $values[$i];
    }

    $update = implode($update,', ');

    $query = "UPDATE {$tableName} SET {$update}";

    if ($conditionArray) {
      list($columns, $values, $conditions) = $this->splitArray($conditionArray);

      for ($i = 0; $i < count($columns); $i++) {
        if (!$conditions[$i]) $csym = $this->comparisonTypes['e'];
        elseif (isset($this->comparisonTypes[$conditions[$i]])) $csym = $this->comparisonTypes[$conditions[$i]];
        else $this->triggerError("[Delete Table] Unrecognised Comparison Type", array(
          'tableName' => $tableName,
          'comparisonType' => $conditions[$i],
        ), 'validation');

        $cond[] = $columns[$i] . $csym . $values[$i];
      }

      $query .= ' WHERE ' . implode($cond, $this->concatTypes['both']);
    }


    return $this->rawQuery($query);
  }
  
  /*********************************************************
  ************************* END ***************************
  ******************** Row Functions **********************
  *********************************************************/
  
}
?>