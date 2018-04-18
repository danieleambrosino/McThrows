<?php

/*
 * This file is part of the McThrows package.
 * 
 * (c) 2018 Daniele Ambrosino <mail@danieleambrosino.it>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file distributed with this source code.
 */

require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

/**
 * Implementation of DataFetcherDao interface with SQLite DBMS.
 *
 * @author Daniele Ambrosino <mail@danieleambrosino.it>
 */
class DataFetcherDaoSQLite implements DataFetcherDao
{

  /**
   * Database handle.
   * 
   * @var SQLite3
   */
  private $db;

  public function __construct()
  {
    $this->db = new SQLite3(SQLITE_DB_PATH, SQLITE3_OPEN_READONLY);
  }

  public function getThrows(string $username): array
  {
    $query = "SELECT throws.throw, count(*) as throw_number\n"
        . "FROM throws\n"
        . "JOIN users ON throws.user_id = users.id\n"
        . "JOIN dice ON throws.die_id = dice.id\n"
        . "WHERE dice.type = ?";
    if (!empty($username))
    {
      $query = $query
          . " AND users.name = ?";
      $values[1] = $username;
    }

    $query = $query
        . "\nGROUP BY throws.throw\n"
        . "ORDER BY throws.throw ASC";

    // per consentire un controllo sulla scelta (e un possibile futuro ampliamento)
    // dei tipi di dadi da considerare
    $dice_types = [4, 6, 8, 10, 12, 20];

    $throws_set = [];

    foreach ($dice_types as $die_type)
    {
      $values[0] = $die_type;
      $result = SQLite3Utils::bindAndExecute($this->db, $query, $values);
      $result = SQLite3Utils::fetchAll($result);
      if (!$result)
      {
        continue;
      }

      for ($i = 0; $i < $die_type; ++$i)
      {
        $throws[$i] = 0;
      }

      foreach ($result as $row)
      {
        $throws[$row['throw'] - 1] = $row['throw_number'];
      }
      $die = 'd' . $die_type;
      $throws_set[$die] = $throws;
    }

    return $throws_set;
  }

  public function getUsers(): array
  {
    $query = "SELECT name FROM users ORDER BY name ASC";
    $result = $this->db->query($query);
    $result = SQLite3Utils::fetchAll($result);
    $users = [];

    for ($i = 0; $i < count($result) && $result !== FALSE; ++$i)
    {
      $users[] = $result[$i]['name'];
    }
    return $users;
  }

}
