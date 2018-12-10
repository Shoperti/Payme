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

namespace Shoperti\PayMe\Gateways\SrPago;

/**
 * Class Encryption
 *
 * @package SrPago\Util
 */
class Encryption  {

    const SRPAGO_RSA_PUBLIC_KEY = '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAv0utLFjwHQk+1aLjxl9t
Ojvt/qFD1HfMFzjYa4d3iFKrQtvxaWM/B/6ltPn6+Pez+dOd59zFmzNHg33h8S0p
aZ6wmNv3mwp4hCJttGzFvl2hhw8Z+OU9KwGSXgQ+5FNyRyDLp0qt75ayvV0vV8oX
0Pgubd/NTHzRKk0ubXO8WVWkNhMdsv0HGrhIMDXAWLAQBzDewmICVH9MIJzjoZym
R7AuNpefD4hoVK8cBMjZ0xRKSPyd3zI6uJyERcR3+N9nxvg4guShP27cnD9qpLt4
L6YtU0BU+husFXoHL6Y2CsxyzxT9mtorAGe5oRiTC7Z/S9u7pxGN4iozgmAei0MZ
VbKows/qa9/q0PPzbF/PHSZKou1DJvsJ2PKY3ZPYAT7/u4x8NRiJ/6cssuzsIPUd
Q9HBzA1ZBMHkpOmkipu1G7ks/GwTfQJkHPW5xHu1EOYvgv/PHr3BJnCMNYKFvf5c
4Qd0COnnU3jDel1OKl7lUzr+ioqUedX393D/fszdK4hjvtUjo6ThTRNm3y4avY/r
m+oLu8sZWpyBm4PfN2xGOnFco9SiyCT03XOEuOXokid6BDMi0aue9LKJaQR+KGVc
/H2p2d2Yu4GdgXS1vq1syaf7V0QPOmamTOyJRZ45UoLfBRB8nYBGDo0mPR7GIon6
M8SmGGsTo3V0L+Ni9bNJHa8CAwEAAQ==
-----END PUBLIC KEY-----';

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
        openssl_public_encrypt($randomKey32, $resultKey, self::SRPAGO_RSA_PUBLIC_KEY);
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
