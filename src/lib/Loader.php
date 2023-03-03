<?php

namespace Lib;

class FetchException extends \Exception {
}

class Loader {
    /**
     * @throws FetchException
     */
    public static function fetch(string $url, $retries = 3) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_ACCEPT_ENCODING => "",
            CURLOPT_HTTPHEADER => [
                "Accept: text/html,application/xhtml+xml",
                "Accept-Encoding: gzip, deflate",
                "Cookie: eCT/LANG=en; eCT/first_user_request=%5B%5D; eCT/perpage=100",
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        if ($error) {
            curl_close($ch);
            if ($retries > 0) {
                return self::fetch($url, $retries - 1);
            } else {
                throw new FetchException('Cannot download ' . $url . ':' . PHP_EOL . $error);
            }
        }
        curl_close($ch);
        return $body;
    }
}