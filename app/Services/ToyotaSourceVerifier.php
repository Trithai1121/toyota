<?php

namespace App\Services;

final class ToyotaSourceVerifier
{
    public function isSecureUrl(?string $url): bool
    {
        return is_string($url)
            && filter_var($url, FILTER_VALIDATE_URL) !== false
            && strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https';
    }

    public function isToyotaOfficialUrl(?string $url): bool
    {
        if (! $this->isSecureUrl($url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === 'toyota.com.vn' || str_ends_with($host, '.toyota.com.vn');
    }

    public function isOfficialGovernmentUrl(?string $url): bool
    {
        if (! $this->isSecureUrl($url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === 'chinhphu.vn'
            || str_ends_with($host, '.chinhphu.vn')
            || str_ends_with($host, '.gov.vn');
    }
}
