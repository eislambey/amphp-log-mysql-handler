<?php

require __DIR__ . "/../vendor/autoload.php";

use Amp\Loop;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

$connectionString = "host=localhost;user=user;pass=password;db=database";

Loop::run(function ()  use ($connectionString){
    $config = Amp\Mysql\ConnectionConfig::fromString($connectionString);
    /** @var Amp\Mysql\Connection $db */
    $connection = yield Amp\Mysql\connect($config);

    $handler = new Islambey\Amp\Log\MySQLHandler($connection);
    $handler->setFormatter(new LineFormatter);

    $logger = new Logger("app");
    $logger->pushHandler($handler);

    $logger->info("foo bar");
});
