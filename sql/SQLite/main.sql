/* 
 * This file is part of the McThrows package.
 * 
 * (c) 2018 Daniele Ambrosino <mail@danieleambrosino.it>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file distributed with this source code.
 */
/**
 * Author:  Daniele Ambrosino <mail@danieleambrosino.it>
 * Created: 10-apr-2018
 */

DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS dice;
DROP TABLE IF EXISTS throws;
DROP TABLE IF EXISTS limbo_users;
DROP TABLE IF EXISTS insertions;
DROP TABLE IF EXISTS last_pending_insertion;

CREATE TABLE IF NOT EXISTS limbo_users (
  telegram_id INTEGER PRIMARY KEY
) WITHOUT ROWID;

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY,
  telegram_id INTEGER NOT NULL UNIQUE,
  name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS dice (
  id INTEGER PRIMARY KEY,
  type TEXT NOT NULL UNIQUE
);

INSERT INTO dice (type) VALUES
(4), (6), (8), (10), (12), (20);

CREATE TABLE IF NOT EXISTS throws (
  id INTEGER PRIMARY KEY,
  user_id NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  die_id NOT NULL REFERENCES dice(id) ON UPDATE CASCADE,
  throw INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS insertions (
  id INTEGER PRIMARY KEY,  
  user_id NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  die_id REFERENCES dice(id) ON UPDATE CASCADE,
  throw INTEGER
);

CREATE TABLE IF NOT EXISTS last_pending_insertion (
  user_id INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
  insertion_id INTEGER NOT NULL REFERENCES insertions(id) ON DELETE CASCADE
) WITHOUT ROWID;
