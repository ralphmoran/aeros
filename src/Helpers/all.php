<?php
/*
|----------------------------------------------
| Load ENV variables
|----------------------------------------------
|
*/

Dotenv\Dotenv::createImmutable(__DIR__ . '/../../../../..', ['.env', '.env.local'])->safeLoad();

/*
|----------------------------------------------
| Global functions
|----------------------------------------------
|
*/

require_once 'framework.php';
require_once 'miscellaneous.php';
require_once 'strings.php';
