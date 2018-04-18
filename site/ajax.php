<?php

/*
 * This file is part of the McThrows package.
 * 
 * (c) 2018 Daniele Ambrosino <mail@danieleambrosino.it>
 * 
 * For the full copyright and license information, please view the LICENSE
 * file distributed with this source code.
 */

if (!isset($_GET['method']))
{
  exit;
}

if (is_string($_GET['method']))
{
  $method = filter_input(INPUT_GET, 'method', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
  if (!in_array($method, ['getThrows', 'getUsers']))
  {
    exit;
  }
  if (isset($_GET['parameters']))
  {
    $parameters = filter_input(INPUT_GET, 'parameters', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    if (!is_array($parameters))
    {
      exit;
    }
  }
}

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$dao = new DataFetcherDaoSQLite();

if ($method === 'getUsers')
{
  $response = json_encode($dao->getUsers());
}
elseif ($method === 'getThrows')
{
  if (!isset($parameters[0]))
  {
    exit;
  }
  if ($parameters[0] === 'Overall')
  {
    $name = "";
  }
  else
  {
    $name = filter_var($parameters[0], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
  }
  $response = json_encode($dao->getThrows($name));
}

header("Content-Type: application/json");
echo $response;
