<?php

namespace App\Services\Security;

class TotpService
{
    public function generateSecret(int $length = 32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';

        for ($i = 0; $i < $length; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $secret;
    }

    public function getProvisioningUri(string $appName, string $accountName, string $secret, int $digits = 6, int $period = 30): string
    {
        $issuer = rawurlencode($appName);
        $label = rawurlencode($appName.':'.$accountName);

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&digits={$digits}&period={$period}";
    }

    public function verifyCode(string $secret, string $code, int $window = 1, int $digits = 6, int $period = 30): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== $digits) {
            return false;
        }

        $timeSlice = (int) floor(time() / $period);

        for ($i = -$window; $i <= $window; $i++) {
            $calculated = $this->calculateCode($secret, $timeSlice + $i, $digits);
            if (hash_equals($calculated, $code)) {
                return true;
            }
        }

        return false;
    }

    private function calculateCode(string $secret, int $timeSlice, int $digits = 6): string
    {
        $secretKey = $this->base32Decode($secret);
        $time = pack('N*', 0, $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);

        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = (
            ((ord($hash[$offset + 0]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        );

        $modulo = 10 ** $digits;
        return str_pad((string) ($value % $modulo), $digits, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $secret): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret));

        $bits = '';
        foreach (str_split($secret) as $char) {
            $position = strpos($alphabet, $char);
            if ($position === false) {
                continue;
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $bytes .= chr(bindec($byte));
            }
        }

        return $bytes;
    }
}
