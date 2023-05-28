<?php

namespace Classes;

class Middleware
{
    public function handle(Request $request, Callable $next) {
        // Implement middleware logic
        return $next($request);
    }
}


/*
In PHP, the Middleware pattern is a design pattern that allows you to handle HTTP 
requests and responses in a specific order by using a stack of middleware classes. 
Each middleware class can perform a specific task, such as validating a user's 
session, logging requests, or handling CORS headers, before passing the request 
and response objects to the next middleware class in the stack.

For example, consider a PHP application that requires users to be logged in 
before accessing certain routes. You could create a middleware class called 
"AuthenticationMiddleware" that checks if a user is logged in before passing 
the request to the next middleware class in the stack.
*/

class AuthenticationMiddleware
{
    public function __invoke($request, $response, $next)
    {
        if (!isLoggedIn()) {
            return $response->withStatus(401);
        }
        return $next($request, $response);
    }
}


/*
Another example, is a middleware class called "CorsMiddleware" that handles 
cross-origin resource sharing (CORS) headers.
*/

class CorsMiddleware
{
    public function __invoke($request, $response, $next)
    {
        $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        $response = $response->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization');
        $response = $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        return $next($request, $response);
    }
}


// Useage

$app = new App();
$app->add(new AuthenticationMiddleware());
$app->add(new CorsMiddleware());
