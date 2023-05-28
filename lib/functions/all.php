<?php
/*
|----------------------------------------------
| Load ENV variables
|----------------------------------------------
|
*/

Dotenv\Dotenv::createImmutable(__DIR__ . '/../..')
    ->load();

/*
|----------------------------------------------
| Global functions
|----------------------------------------------
|
*/

require_once 'framework.php';
require_once 'miscellaneous.php';
require_once 'strings.php';
