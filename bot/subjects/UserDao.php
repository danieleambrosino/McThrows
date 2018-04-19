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
 * Data Access Object interface for User entity.
 * 
 * @author Daniele Ambrosino <mail@danieleambrosino.it>
 */
interface UserDao
{
  
  /**
   * Get total throws number of a user.
   * 
   * @param int $telegramId
   */
  public function getThrowsCount(): int;
  
  /**
   * Add a user.
   * 
   * @param string name
   * @param int $telegramId
   * @return bool
   */
  public function addUser(string $name): bool;
  
  /**
   * Check if a user is stored.
   * 
   * @param int $telegramId
   * @return bool
   */
  public function isStored(): bool;
  
  /**
   * Add a user into limbo (that is, pending registration status).
   * 
   * @param int $telegramId
   */
  public function addToLimbo();
  
  /**
   * Check if a user is in the limbo.
   * 
   * @param int $telegramId
   * @return bool
   */
  public function inLimbo(): bool;
  
  /**
   * Get the user's nickname.
   * 
   * @param int $telegramId
   * @return string User's nickname.
   */
  public function getUsername(): string;
  
  /**
   * Set the user's nickname.
   * 
   * @param int $telegramId
   * @param string $name
   */
  public function setUsername(string $name): bool;
  
  /**
   * Remove a user from the limbo.
   * 
   * @param int $telegramId
   */
  public function removeFromLimbo(): bool;
  
  /**
   * Delete a user.
   * 
   * @param int $telegramId
   */
  public function deleteUser(): bool;
  
  /**
   * Check if a user is inserting some throw.
   * 
   * @param int $telegramId
   */
  public function isInserting(): bool;
  
  /**
   * Insert a die type.
   * 
   * @param int $telegramId
   * @param int $dieType
   * @return bool TRUE on success, FALSE otherwise.
   */
  public function insertDie(int $dieType): bool;
  
  /**
   * Insert a throw.
   * 
   * @param int $telegramId
   * @param int $throw
   */
  public function insertThrow(int $throw): bool;
  
  /**
   * Start an insertion.
   * 
   * @param int $telegramId
   */
  public function startInsertion(): bool;
  
  /**
   * Finalyze all pending insertions.
   * 
   * @param int $telegramId
   */
  public function finalyzeInsertions(): bool;
  
  /**
   * Cancel all pending insertions.
   * 
   * @param int $telegramId
   */
  public function cancelInsertions(): bool;
  
  /**
   * Return the step (1 or 2) of insertion.
   * 
   * @param int $telegramId
   * @return int insertion step (1 or 2).
   */
  public function getInsertionStep(): int;
  
  /**
   * Get the die type of the pending insertion.
   * 
   * @param int $telegramId
   * @return string|bool String with die type (dXY) on success, FALSE on failure.
   */
  public function getPendingDieType();
  
  public function deletePendingDieType();
}
