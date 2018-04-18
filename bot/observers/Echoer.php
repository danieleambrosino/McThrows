<?php

/*
 * This file is part of the McThrows package.
 * 
 * (c) 2018 Daniele Ambrosino <mail@danieleambrosino.it>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file distributed with this source code.
 */

$autoloadPath = dirname(dirname(__DIR__)) . '/vendor/autoload.php';
if (stripos(PHP_OS, 'win') !== FALSE)
{
  $autoloadPath = str_replace('/', '\\', $autoloadPath);
}
require_once $autoloadPath;

/**
 * Dev utility that dumps to stdout the computed response.
 *
 * @author Daniele Ambrosino <mail@danieleambrosino.it>
 */
class Echoer implements SplObserver
{
  public function update(SplSubject $subject)
  {
    var_dump($subject->getSendingInfo());
  }
}
