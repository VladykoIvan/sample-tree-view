<?php
declare(strict_types=1);

$host = "127.0.0.1";
$dbname = "test";
$username = "admin";
$password = "admin";
// set charset to avoid SQL-injections
$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
