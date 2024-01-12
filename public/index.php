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

# TODO: Add commands to run warmup
# TODO: Implement a warmup process. It requires cache() and cron()
# TODO: Add languages support: translation
# TODO: Use a different email sender system: PHPMailer is recommended
# TODO: Use Aeros namespace to avoid collisions
# TODO: Add start command that loads Mime types, warmup, runs crons, app worker, etc
# TODO: Review route loading. Is it necessary to load web and api routes? See: RouteServiceProvider
# TODO: Cache routes for the warmup. A route is registered by \Aeros\Lib\Classes\Router class

app()->run();
