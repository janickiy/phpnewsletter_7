<?php

namespace App\Services;

final class UrlHostResolver
{
    public function resolve(?string ...$urls): string
    {
        foreach ($urls as $url) {
            if (! is_string($url) || trim($url) === '') {
                continue;
            }

            $host = parse_url($url, PHP_URL_HOST);

            if (is_string($host) && $host !== '') {
                return $host;
            }
        }

        return 'localhost';
    }
}
