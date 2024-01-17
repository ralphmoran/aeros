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
# TODO: Add start command that loads Mime types, warmup, runs crons, app worker, etc. It requires cache() and cron()
# TODO: Review route loading. Is it necessary to load web and api routes? See: RouteServiceProvider
# TODO: Memcached is way faster for small size kay-pair values; Redis is way better on large size and complex types
// See: https://www.digitalocean.com/community/tutorials/how-to-install-and-secure-memcached-on-ubuntu-22-04

app()->run();
