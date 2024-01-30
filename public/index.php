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

# TODO: Add languages support: translation
# TODO: Use a different email sender system: PHPMailer is recommended
# TODO: Review route loading. Is it necessary to load web and api routes? See: RouteServiceProvider
# TODO: Create a class that handle infinite loops. Usage: on workers
# TODO: Update the ecosystem to catch errors, identify them and fix them on the fly. Example: "ERROR 'json' missing in Response class"
    // This error means that cache('myme.types') is null or not available
# TODO: Implement Phinx for all drivers, also, use socket connections (ONLY if client and server are in the same server)
# TODO: Use socket connections for MySQL to improve performance
# TODO: Add broadcasting feature from backend to front end (Axios or Pusher)

app()->run();
