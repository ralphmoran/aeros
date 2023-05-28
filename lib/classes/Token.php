<?php

namespace Classes;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class Token
{
    private $key;
    
    public function __construct($key)
    {
        $this->key = $key;
    }

    public function encode($loginid)
    {
        $issuedAt = new \DateTimeImmutable();
        $expire = $issuedAt->modify('+8 hours')->getTimestamp();

        $payload = array(
            "iat" => $issuedAt->getTimestamp(),
            "nbf" => $issuedAt->getTimestamp(),
            "exp" => $expire,
            "username" => $loginid
        );

        return JWT::encode($payload, env("TOKEN_KEY"), 'HS512');
    }

    public function decode($token)
    {
        try{
            return JWT::decode($token, new Key(env("TOKEN_KEY"), 'HS512'));
        }
        catch (\Exception $e){
            return false;
        }   
    }

    function validate($authorization)
    {
        //authorization: is the header Authorization
        //token: is a Token object

        $jwt = $this->get_auth_token($authorization);

        if (! $jwt) {
            return false;
        }

        if (! $this->decode($jwt)) {
            return false;
        }

        return true;
    }

    public function get_auth_token($authorization)
    { 
        if (! preg_match('/Bearer\s(\S+)/', $authorization, $matches)) {
            return false;
        }

        if (! $matches[1]) {
            return false;
        }

        return $matches[1];
    }
    
}
