<?php

/*
|----------------------------------------------
| List your global middlewares
|----------------------------------------------
|
| Make sure your middlewares are sorted as you expect to work.
|
| These are executed with the FIFO inventory management.
|
*/

return [
    // 'SessionMiddleware' => Middlewares\SessionMiddleware::class,
    'CorsMiddleware' => Middlewares\CorsMiddleware::class,
    'BanBotsMiddleware' => Middlewares\BanBotsMiddleware::class,
    'SanitizerMiddleware' => Middlewares\SanitizerMiddleware::class,
];
