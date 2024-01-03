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

# TODO: Implement a warmup process. It requires cache() and cron()
# TODO: Add commands to run warmup
# TODO: Add languages support: translation
# TODO: Use a different email sender system

app()->run();
