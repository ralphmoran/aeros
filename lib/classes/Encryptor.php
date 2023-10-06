<?php

namespace Classes;

class Encryptor 
{
    /** @var string */
    private $key;
    
    /** @var string */
    private $ciphering;
    
    /** @var string */
    private $iv_length;

    public function __construct() {
        $this->key = env('APP_KEY');
        $this->ciphering = "AES-256-CBC";
        $this->iv_length = openssl_cipher_iv_length($this->ciphering);
    }

    /**
     * Formats a URL with base64_encode.
     *
     * @param string $string
     * @return string
     */
    function base64_encode_url($string) {
        return str_replace(['+','/','='], ['-','_',''], base64_encode($string));
    }
    
    /**
     * Formats a URL with base64_decode.
     *
     * @param string $string
     * @return string
     */
    function base64_decode_url($string) {
        return base64_decode(str_replace(['-','_'], ['+','/'], $string));
    }

    /**
     * OpenSSL Encrypts a value.
     *
     * @param int $id
     * @return string
     */
    function encrypt($id){
        $encryption_iv = random_bytes($this->iv_length);

        $encryption = openssl_encrypt(
            $id, 
            $this->ciphering, 
            $this->key, 
            0, 
            $encryption_iv
        );

        return $this->base64_encode_url($encryption_iv . $encryption);
    }

    /**
     * OpenSSL Decrypts a value.
     *
     * @param int $id
     * @return string
     */
    function decrypt($id){
        $decoded64 = $this->base64_decode_url($id);

        return openssl_decrypt(
            mb_substr( $decoded64, $this->iv_length, null, '8bit'), 
            $this->ciphering, 
            $this->key, 
            0, 
            mb_substr($decoded64, 0, $this->iv_length, '8bit')
        );
    }
}

/*
 |------------------------------------
 | Encryptor helpers 
 |------------------------------------
 */

// /**
//  * Encrypts permanently a value.
//  *
//  * @param int $number
//  * @param int $borrowerid
//  * @return string
//  */
// function encrypt_perm($number, $borrowerid)
// {
//     $clean = filter_var(
//         preg_replace('/[^0-9]/', '', $number), 
//         FILTER_SANITIZE_NUMBER_INT
//     );

//     return (openssl_encrypt($clean, "AES-128-ECB", env('PERM_KEY') . $borrowerid) ? openssl_encrypt($clean, "AES-128-ECB", env('PERM_KEY') . $borrowerid) : '');
// }

// /**
//  * Decrypts a value.
//  *
//  * @param string $encrypted
//  * @param int $borrowerid
//  * @return string
//  */
// function decrypt_perm($encrypted, $borrowerid)
// {
//     return openssl_decrypt(
//         $encrypted, 
//         "AES-128-ECB", 
//         env('PERM_KEY') . $borrowerid
//     );
// }

// /**
//  * Encrypts DOB.
//  *
//  * @param string $dob
//  * @param int $borrowerid
//  * @return string
//  */
// function encrypt_dob($dob, $borrowerid)
// {
//     date_default_timezone_set('UTC');

//     if(!empty($dob)){
//         $unix_dob = DateTime::createFromFormat('m/d/Y', $dob);

//         return (openssl_encrypt(
//             $unix_dob->getTimestamp(),
//             "AES-128-ECB",
//             env('PERM_KEY') . $borrowerid
//         )
//             ? openssl_encrypt(
//                 $unix_dob->getTimestamp(),
//                 "AES-128-ECB",
//                 env('PERM_KEY') . $borrowerid
//             )
//             : '');
//     }

//     return '';    
// }

// /**
//  * Decrypts DOB.
//  *
//  * @param string $encrypted
//  * @param int $borrowerid
//  * @return string
//  */
// function decrypt_dob($encrypted, $borrowerid)
// {
//     date_default_timezone_set('UTC');

//     if (!empty($encrypted)) {
//         $unix_dob = openssl_decrypt(
//             $encrypted,
//             "AES-128-ECB",
//             env('PERM_KEY') . $borrowerid
//         );

//         return date('m/d/Y', $unix_dob);
//     }
//     return '';
// }
