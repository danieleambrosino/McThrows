<?php

/*
 * This file is part of the McThrows package.
 * 
 * (c) 2018 Daniele Ambrosino <mail@danieleambrosino.it>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file distributed with this source code.
 */

$update = file_get_contents('php://input');
if (empty($update))
{
  exit;
}

$remoteAddress = filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
if (!$remoteAddress ||
    !((substr($remoteAddress, 0, 12) === '149.154.167.') && 
    (intval(substr($remoteAddress, 12, 3)) >= 197 && intval(substr($remoteAddress, 12, 3)) <= 233)))
{
  echo "<h1>E tu chi sei, signor $remoteAddress? \u{1F914}</h1>";
  return;
}

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$user = new User($update);

if (defined('DEVELOPMENT'))
{
  $observer = new Echoer();
}
else
{
  $observer = new Communicator();
}
$user->attach($observer);

$user->run();
