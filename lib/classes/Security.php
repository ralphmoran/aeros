<?php

namespace Classes;

class Security extends Kernel
{
    /**
     * Creates a hidden input with a token. This token helps to validate if a 
     * request is authorized.
     *
     * @return string
     */
    public function csrf(): string
    {
        return component('inputs.hidden', [
            'id' => 'csrf_token',
            'name' => 'csrf_token',
            'value' => $_SESSION['token'],
            ], 
            true
        );
    }
}
