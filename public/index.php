<?php

/*
|----------------------------------------------
| Index - Main point
|----------------------------------------------
|
| All requests land here for future evaluation.
|
*/

require_once __DIR__ . '/../src/aeros_autoload.php';

# TODO: Use a different email sender system: PHPMailer is recommended
# TODO: Use socket connections for MySQL to improve performance
# TODO: Add broadcasting feature from backend to front end (Axios or Pusher): https://packagist.org/packages/cboden/ratchet
# TODO: Implement cache system for views, DB queries. DB queries can be hashed using the APP_KEY 
# TODO: Implement headers to improve performance and CORS
# TODO: Minify files CSS, JS, images
# TODO: Implement an active object pool. Store objects in memcached: https://stackoverflow.com/questions/16809953/can-memcached-store-complex-objects
# TODO: Simulate HTTPS protocol on local development
# TODO: Add a new command to flush cache

app()->run();
