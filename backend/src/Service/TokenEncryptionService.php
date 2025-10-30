<?php

namespace App\Service;

class TokenEncryptionService
{
    private const CIPHER_ALGO = 'aes-256-cbc';
    private const IV_LENGTH = 16;

    private readonly string $encryptionKey;

    public function __construct(string $appSecret)
    {
        if (empty($appSecret)) {
            throw new \InvalidArgumentException('APP_SECRET cannot be empty');
        }

        $this->encryptionKey = hash('sha256', $appSecret, true);
    }

    public function encrypt(string $plainText): string
    {
        if (empty($plainText)) {
            throw new \InvalidArgumentException('Cannot encrypt empty string');
        }

        $iv = openssl_random_pseudo_bytes(self::IV_LENGTH);

        $encrypted = openssl_encrypt(
            $plainText,
            self::CIPHER_ALGO,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $encryptedText): string
    {
        if (empty($encryptedText)) {
            throw new \InvalidArgumentException('Cannot decrypt empty string');
        }

        $data = base64_decode($encryptedText, true);

        if ($data === false) {
            throw new \RuntimeException('Invalid base64 encoding');
        }

        $iv = substr($data, 0, self::IV_LENGTH);
        $encrypted = substr($data, self::IV_LENGTH);

        if (strlen($iv) !== self::IV_LENGTH) {
            throw new \RuntimeException('Invalid IV length');
        }

        $decrypted = openssl_decrypt(
            $encrypted,
            self::CIPHER_ALGO,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $decrypted;
    }
}
