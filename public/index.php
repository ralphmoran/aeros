<?php
/*
|----------------------------------------------
| Index - Main point
|----------------------------------------------
|
| All requests land here for future evaluation.
|
*/

require_once __DIR__ . '/../vendor/autoload.php';


$a = [
    'a'
];

if (isset($a['a'])) {
    echo 'a is set ...';
}

app()->run();
