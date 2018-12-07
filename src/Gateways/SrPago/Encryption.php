<?php

/**
 *
 * Sr. Pago (https://srpago.com)
 *
 * @link      https://api.srpago.com
 * @copyright Copyright (c) 2016 SR PAGO
 * @license   http://opensource.org/licenses/BSD-2-Clause BSD-2-Clause
 * @package   SrPago\Util
 */

namespace PayMe\Gateways\SrPago;

/**
 * Class Encryption
 *
 * @package SrPago\Util
 */
class Encryption  {

    const KEY_LENGTH = 32;
    const CHARACTERS = '0123456789abcdef';

    /**
     *
     * @param string $parametersJson
     * @return array
     */
    public static function encryptParametersWithString($parametersJson){
        if(!is_string($parametersJson)){
            $parametersJson = json_encode($parametersJson);
        }

        $randomKey32 = static::generateRandomString(static::KEY_LENGTH);

        $resultKey = null;
        openssl_public_encrypt($randomKey32, $resultKey, \SrPago\SrPago::SRPAGO_RSA_PUBLIC_KEY);
        $key = base64_encode($resultKey);

        if (version_compare(PHP_VERSION, '7.1.0', '>=')) {
        	$resultData = openssl_encrypt($parametersJson, 'AES-256-ECB', $randomKey32, OPENSSL_RAW_DATA);
        } else {
        	$resultData = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $randomKey32, $parametersJson, MCRYPT_MODE_ECB);
        }

        $data = base64_encode($resultData);
        return array('key'=>$key,'data'=>$data);
    }

   /**
    *
    * @param int $length
    * @return string
    */
    protected static function generateRandomString($length)
    {
        $characters = static::CHARACTERS;

        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    protected static function encrypt($plaintext, $key)
    {
        $ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
        return $iv.$hmac.$ciphertext_raw;
    }
}
