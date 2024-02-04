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

# TODO: Use a different email sender system: PHPMailer is recommended
# TODO: Use socket connections for MySQL to improve performance
# TODO: Add broadcasting feature from backend to front end (Axios or Pusher): https://packagist.org/packages/cboden/ratchet

app()->run();
