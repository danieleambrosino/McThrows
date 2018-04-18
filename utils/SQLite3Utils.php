<?php

/*
 * This file is part of the McThrows package.
 * 
 * (c) 2018 Daniele Ambrosino <mail@danieleambrosino.it>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file distributed with this source code.
 */

/**
 * SQLite3 utilities to execute prepared statements and fetch data.
 *
 * @author Daniele Ambrosino <mail@danieleambrosino.it>
 */
class SQLite3Utils
{
  /**
   * Fetch all results from a SQLite3Result object.
   * 
   * @param SQLite3Result $sqlResult
   * @return array|bool
   */
  public static function fetchAll(SQLite3Result $sqlResult)
  {
    $results = FALSE;
    while ($fetch = $sqlResult->fetchArray(SQLITE3_ASSOC)) {
      $results[] = $fetch;
    }
    return $results;
  }
  
  /**
   * Bind and execute a prepared query.
   * 
   * @param SQLite3 $db SQLite3 handle.
   * @param string $query Query to execute.
   * @param array $values Values to bind.
   * @return SQLite3Result|bool resource on success, FALSE on failure.
   */
  public static function bindAndExecute(SQLite3 $db, string $query, array $values)
  {
    assert((substr_count($query, '?') == count($values)), new ErrorException(__METHOD__ . 'Parameters count mismatch'));

    $stmt = $db->prepare($query);
    if (!$stmt) {
      return FALSE;
    }
    for ($i = 0; $i < count($values); ++$i) {
      $stmt->bindValue($i + 1, $values[$i], self::detectAffinity($values[$i]));
    }
    $result = $stmt->execute();

    return $result;
  }
  
  /**
   * Detect the SQLite corresponding affinity of a value.
   * 
   * @param mixed $value Value to analyze.
   * @return int|bool Corresponding affinity, FALSE on failure.
   */
  private static function detectAffinity($value): int
  {
    switch (gettype($value)) {
      case 'string':
        return SQLITE3_TEXT;
      case 'integer':
        return SQLITE3_INTEGER;
      case 'NULL':
        return SQLITE3_NULL;
      default:
        return FALSE;
    }
  }
}
