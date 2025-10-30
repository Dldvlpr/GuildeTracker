<?php

namespace App\Service;

class TokenEncryptionService
{
    private const CIPHER_ALGO_GCM = 'aes-256-gcm';
    private const NONCE_LENGTH = 12;
    private const TAG_LENGTH = 16;
    private const PREFIX_V2 = 'v2:';

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
        if ($plainText === '') {
            throw new \InvalidArgumentException('Cannot encrypt empty string');
        }

        $nonce = random_bytes(self::NONCE_LENGTH);
        $tag = '';
        $cipher = openssl_encrypt(
            $plainText,
            self::CIPHER_ALGO_GCM,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($cipher === false || $tag === '') {
            throw new \RuntimeException('Encryption failed');
        }

        return self::PREFIX_V2 . base64_encode($nonce . $tag . $cipher);
    }

    public function decrypt(string $encryptedText): string
    {
        if ($encryptedText === '') {
            throw new \InvalidArgumentException('Cannot decrypt empty string');
        }

        if (!str_starts_with($encryptedText, self::PREFIX_V2)) {
            throw new \RuntimeException('Unsupported ciphertext format');
        }

        $b64 = substr($encryptedText, strlen(self::PREFIX_V2));
        $data = base64_decode($b64, true);
        if ($data === false) {
            throw new \RuntimeException('Invalid base64 encoding');
        }

        $nonce = substr($data, 0, self::NONCE_LENGTH);
        $tag = substr($data, self::NONCE_LENGTH, self::TAG_LENGTH);
        $cipher = substr($data, self::NONCE_LENGTH + self::TAG_LENGTH);

        if (strlen($nonce) !== self::NONCE_LENGTH || strlen($tag) !== self::TAG_LENGTH || $cipher === '') {
            throw new \RuntimeException('Invalid ciphertext format');
        }

        $plain = openssl_decrypt(
            $cipher,
            self::CIPHER_ALGO_GCM,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($plain === false) {
            throw new \RuntimeException('Decryption failed');
        }

        return $plain;
    }
}
