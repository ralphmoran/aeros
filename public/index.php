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
# TODO: Review route loading. Is it necessary to load web and api routes? See: RouteServiceProvider
# TODO: Update the ecosystem to catch errors, identify them and fix them on the fly. Example: "ERROR 'json' missing in Response class"
    // This error means that cache('myme.types') is null or not available
# TODO: Use socket connections for MySQL to improve performance
# TODO: Add broadcasting feature from backend to front end (Axios or Pusher): https://packagist.org/packages/cboden/ratchet

app()->run();
