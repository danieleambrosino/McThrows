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
 *
 * @author Daniele Ambrosino <mail@danieleambrosino.it>
 */
interface DataFetcherDao
{

  /**
   * Get users list.
   * 
   * @return array with users list, in alphabetical order.
   */
  public function getUsers(): array;

  /**
   * Get user's throws.
   * 
   * @param string $username
   */
  public function getThrows(string $username): array;
}
