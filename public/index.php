<?php
//Defining PHP_START will allow cubex to add an execution time header
define('PHP_START', microtime(true));

//Include the composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

\Packaged\Helpers\PackagedHelpers::includeGlobalFunctions();

// Set correct enviroment
if(isset($_SERVER['APPLICATION_ID']) && $_SERVER['APPLICATION_ID'] == 'dev~php-packages-com')
{
  putenv('CUBEX_ENV=LOCAL');
}
else
{
  putenv('CUBEX_ENV=PRODUCTION');
}

//Create an instance of cubex, with the web root defined
$app = new \Cubex\Cubex(__DIR__);
$app->boot();

//Create and configure a new dispatcher
$dispatcher = new \Packaged\Dispatch\Dispatch(
  $app,
  $app->getConfiguration()->getSection('dispatch')
);

//Set the correct working directory for dispatcher
$dispatcher->setBaseDirectory(dirname(__DIR__));

//Load in the cache of file hashes to improve performance of dispatched assets
$fileHash = 'conf/dispatch.filehash.ini';
if(file_exists($fileHash))
{
  $hashTable = parse_ini_file($fileHash, false);
  if(!empty($hashTable))
  {
    $dispatcher->setFileHashTable($hashTable);
  }
}

//Inject dispatch to handle assets
$app = (new \Stack\Builder())->push([$dispatcher, 'prepare'])->resolve($app);

//Create a request object
$request = \Cubex\Http\Request::createFromGlobals();

//Tell Cubex to handle the request, and do its magic
$response = $app->handle($request);

//Send the generated response to the user
$response->send();

//Shutdown Cubex
$app->terminate($request, $response);
