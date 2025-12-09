<?php

namespace App\Service;

class AesCipher
{

    // https://gist.github.com/thomasdarimont/fae409eaae2abcf83bd6633b961e7f00

    private const OPENSSL_CIPHER_NAME = 'aes-128-cbc';
    private const CIPHER_KEY_LEN = 16; //128 bits

    /**
     * Encrypt data using AES Cipher (CBC) with 128 bit key
     *
     * @param string $key - key to use should be 16 bytes long (128 bits)
     * @param string $iv - initialization vector too
     * @param string $data - data to encrypt
     * @return string encrypted data in base64 encoding with iv
     */
    static function encrypt($plainText, $hashKey, $initVector): string
    {
        $encryptedText = openssl_encrypt($plainText, AesCipher::OPENSSL_CIPHER_NAME, $hashKey, OPENSSL_RAW_DATA, $initVector);
        $b64encryptedText = base64_encode($encryptedText);

        return $b64encryptedText;
    }
}