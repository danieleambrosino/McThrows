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
 * Implementation of the Data Access Object for the SQLite3 DBMS.
 *
 * @author Daniele Ambrosino <mail@danieleambrosino.it>
 */
class UserDaoSQLite implements UserDao
{
  
  /**
   * @var SQLite3 Database handle.
   */
  private $db;
  
  /**
   *
   * @var int User's Telegram ID.
   */
  private $telegramId;
  
  public function __construct(int $telegramId)
  {
    $this->db = new SQLite3(SQLITE_DB_PATH);
    $this->db->exec('PRAGMA foreign_keys = ON;');
    $this->telegramId = $telegramId;
  }
  
  public function getThrowsCount(): int
  {
    $query = "SELECT count(*) AS throws_count\n"
        . "FROM throws\n"
        . "JOIN users ON throws.user_id = users.id\n"
        . "WHERE users.telegram_id = ?\n"
        . "GROUP BY users.id";
    $values = [$this->telegramId];
    $result = $this->bindAndExecute($query, $values);
    $result = $this->fetchAll($result);
    if (isset($result[0]['throws_count']))
    {
      return $result[0]['throws_count'];
    }
    else
    {
      return 0;
    }
  }
  
  public function addToLimbo()
  {
    $query = "INSERT INTO limbo_users (telegram_id) VALUES (?)";
    $values[] = $this->telegramId;
    return $this->bindAndExecute($query, $values) !== FALSE;
  }

  public function addUser(string $name): bool
  {
    $query = "INSERT INTO users (name, telegram_id) VALUES (?, ?)";
    $values = [$name, $this->telegramId];
    return $this->bindAndExecute($query, $values) !== FALSE;
  }

    public function inLimbo(): bool
  {
    $query = "SELECT * FROM limbo_users WHERE telegram_id = ?";
    $values = [$this->telegramId];
    $result = $this->bindAndExecute($query, $values);
    return $this->fetchAll($result) !== FALSE;
  }

  public function isStored(): bool
  {
    $query = "SELECT * FROM users WHERE telegram_id = ?";
    $values = [$this->telegramId];
    $result = $this->bindAndExecute($query, $values);
    return $this->fetchAll($result) !== FALSE;
  }

  public function deleteUser(): bool
  {
    $query = "DELETE FROM users WHERE telegram_id = ?";
    $values = [$this->telegramId];
    return $this->bindAndExecute($query, $values) !== FALSE;
  }

  public function removeFromLimbo(): bool
  {
    $query = "DELETE FROM limbo_users WHERE telegram_id = ?";
    $values = [$this->telegramId];
    return $this->bindAndExecute($query, $values) !== FALSE;
  }

  public function isInserting(): bool
  {
    $query = "SELECT insertions.id\n"
        . "FROM insertions\n"
        . "JOIN users ON insertions.user_id = users.id\n"
        . "WHERE users.telegram_id = ?";
    $values = [$this->telegramId];
    $result = $this->bindAndExecute($query, $values);
    return $this->fetchAll($result) !== FALSE;
  }

  public function cancelInsertions(): bool
  { 
    $query = "DELETE FROM insertions\n"
        . "WHERE user_id = (SELECT id\n"
        .                  "FROM users\n"
        .                  "WHERE telegram_id = ?)";
    $values = [$this->telegramId];
    return $this->bindAndExecute($query, $values) !== FALSE;
  }

  public function finalyzeInsertions(): bool
  {    
    $query = "DELETE FROM insertions\n"
        . "WHERE user_id = (SELECT id FROM users WHERE telegram_id = ?)\n"
        .   "AND ((die_id IS NULL) OR (throw IS NULL))";
    $values = [$this->telegramId];
    $this->bindAndExecute($query, $values);
    
    $this->db->exec("BEGIN TRANSACTION");
    
    $query = "INSERT INTO throws (user_id, die_id, throw)\n"
        . "SELECT user_id, die_id, throw\n"
        . "FROM insertions\n"
        . "JOIN users ON insertions.user_id = users.id\n"
        . "WHERE users.telegram_id = ?";
    $values = [$this->telegramId];
    $this->bindAndExecute($query, $values);
    
    $query = "DELETE FROM insertions\n"
        . "WHERE user_id = (SELECT id\n"
        .                  "FROM users\n"
        .                  "WHERE telegram_id = ?)";
    $this->bindAndExecute($query, $values);
    
    return $this->db->exec("COMMIT");
  }

  public function insertDie(int $dieType): bool
  {
    $query = "UPDATE insertions\n"
        . "SET die_id = (SELECT id FROM dice WHERE type = ?)\n"
        . "WHERE id = (SELECT insertion_id\n"
        .             "FROM last_pending_insertion\n"
        .             "JOIN users ON last_pending_insertion.user_id = users.id\n"
        .             "WHERE users.telegram_id = ?)";
    $values = [$dieType, $this->telegramId];
    return $this->bindAndExecute($query, $values) !== FALSE;
  }

  public function insertThrow(int $throw): bool
  {
    $query = "UPDATE insertions\n"
        . "SET throw = ?\n"
        . "WHERE id = (SELECT insertion_id\n"
        .             "FROM last_pending_insertion\n"
        .             "JOIN users ON last_pending_insertion.user_id = users.id\n"
        .             "WHERE users.telegram_id = ?)";
    $values = [$throw, $this->telegramId];
    return $this->bindAndExecute($query, $values) !== FALSE;
  }
  
  public function startInsertion(): bool
  {
    $this->db->exec("BEGIN TRANSACTION");
    
    $query = "INSERT INTO insertions (user_id)\n"
        . "SELECT id\n"
        . "FROM users\n"
        . "WHERE telegram_id = ?";
    $values = [$this->telegramId];
    $this->bindAndExecute($query, $values);
    
    $query = "INSERT OR REPLACE INTO last_pending_insertion (user_id, insertion_id)\n"
        . "SELECT users.id, insertions.id\n"
        . "FROM insertions\n"
        . "JOIN users ON insertions.user_id = users.id\n"
        . "WHERE users.telegram_id = ?";
    $this->bindAndExecute($query, $values);
    
    return $this->db->exec("COMMIT");
  }
  
  public function getUsername(): string
  {
    $query = "SELECT name FROM users WHERE telegram_id = ?";
    $values = [$this->telegramId];
    $result = $this->bindAndExecute($query, $values);
    $result = $this->fetchAll($result);
    if ($result === FALSE)
    {
      return "";
    }
    return $result[0]['name'];
  }
  
  public function setUsername(string $name): bool
  {
    $query = "UPDATE users\n"
        . "SET name = ?\n"
        . "WHERE telegram_id = ?";
    $values = [$name, $this->telegramId];
    return $this->bindAndExecute($query, $values) !== FALSE;
  }

  
  public function getInsertionStep(): int
  {
    $query = "SELECT insertions.die_id, insertions.throw\n"
        . "FROM insertions\n"
        . "JOIN last_pending_insertion ON insertions.id = last_pending_insertion.insertion_id\n"
        . "WHERE insertions.user_id = (SELECT id FROM users WHERE telegram_id = ?)";
    $values = [$this->telegramId];
    $result = $this->bindAndExecute($query, $values);
    $result = $this->fetchAll($result);
    if ($result === FALSE)
    {
      return 0;
    }
    if (empty($result[0]['die_id']))
    {
      return 1;
    } else
    {
      return 2;
    }
  }
  
  public function getPendingDieType()
  {
    $query = "SELECT dice.type\n"
        . "FROM last_pending_insertion\n"
        . "JOIN users ON last_pending_insertion.user_id = users.id\n"
        . "JOIN insertions ON last_pending_insertion.insertion_id = insertions.id\n"
        . "JOIN dice ON insertions.die_id = dice.id\n"
        . "WHERE users.telegram_id = ?";
    $values = [$this->telegramId];
    $result = $this->bindAndExecute($query, $values);
    $result = $this->fetchAll($result);
    if ($result === FALSE)
    {
      return FALSE;
    }
    if (!empty($result[0]['type']))
    {
      return $result[0]['type'];
    }
    return FALSE;
  }
  
  /**
   * Provides a wrapper for the SQLite3Utils::fetchAll static method.
   * 
   * @param SQLite3Result $sqlResult
   * @return array|bool
   */
  private function fetchAll(SQLite3Result $sqlResult)
  {
    return SQLite3Utils::fetchAll($sqlResult);
  }
  
  /**
   * Provides a wrapper for the SQLite3Utils::bindAndExecute static method.
   * 
   * @param string $query Query to execute.
   * @param array $values Values to bind.
   * @return SQLite3Result|bool resource on success, FALSE on failure.
   */
  private function bindAndExecute(string $query, array $values)
  {
    return SQLite3Utils::bindAndExecute($this->db, $query, $values);
  }
  
}
