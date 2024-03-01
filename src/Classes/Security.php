<?php

namespace Aeros\Src\Classes;

class Security
{
    /**
     * Creates a hidden input with a token. This token helps to validate if a 
     * request is authorized.
     *
     * @return string
     */
    public function csrf()
    {
        return component(
            'inputs.hidden', 
            [
                'id' => 'csrf_token',
                'name' => 'csrf_token',
                'value' => session()->csrf_token,
            ]
        );
    }
}
