<?php

namespace App\Core;

use RuntimeException;

/**
 * Criptografia simetrica (AES-256-CBC + HMAC) para valores sensiveis
 * armazenados na tabela settings (chaves de API, tokens, etc).
 */
class Encrypter
{
    protected string $key;
    protected string $cipher = 'AES-256-CBC';

    public function __construct(string $key)
    {
        // A chave pode vir em base64 (prefixo base64:) ou texto puro.
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }
        // Normaliza para 32 bytes.
        $this->key = hash('sha256', $key, true);
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        $cipherText = openssl_encrypt($plaintext, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($cipherText === false) {
            throw new RuntimeException('Falha ao criptografar valor.');
        }
        $mac = hash_hmac('sha256', $iv . $cipherText, $this->key, true);
        return base64_encode($iv . $mac . $cipherText);
    }

    public function decrypt(string $payload): string
    {
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            throw new RuntimeException('Payload criptografado invalido.');
        }
        $ivLen = openssl_cipher_iv_length($this->cipher);
        $macLen = 32;

        $iv = substr($decoded, 0, $ivLen);
        $mac = substr($decoded, $ivLen, $macLen);
        $cipherText = substr($decoded, $ivLen + $macLen);

        $calcMac = hash_hmac('sha256', $iv . $cipherText, $this->key, true);
        if (!hash_equals($mac, $calcMac)) {
            throw new RuntimeException('Falha na verificacao de integridade (MAC).');
        }

        $plaintext = openssl_decrypt($cipherText, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        if ($plaintext === false) {
            throw new RuntimeException('Falha ao descriptografar valor.');
        }
        return $plaintext;
    }
}
