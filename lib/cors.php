<?php
/*
|----------------------------------------------
| Origin headers
|----------------------------------------------
|
| Dynamically loading/adding header to avoid CORS issues.
|
*/
header("Access-Control-Allow-Origin: " . (array_key_exists('HTTP_ORIGIN', $_SERVER) 
                                            ? $_SERVER['HTTP_ORIGIN'] 
                                            : $_SERVER['HTTP_HOST'])
                                        );
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE');
header('Access-Control-Allow-Headers: Origin, Authorization, Content-type');
header("Access-Control-Max-Age: 3600");
