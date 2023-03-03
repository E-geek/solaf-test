<?php

namespace Tool;

class URL {
    public static function linkToURL(string $link, string $base) :string {
        $parsedUrl = parse_url($base);
        $root = ($parsedUrl[ 'scheme' ] ?? 'https') . '://'
            . $parsedUrl[ 'host' ]
            . (isset($parsedUrl[ 'port' ]) ? ':' . $parsedUrl[ 'port' ] : '');
        if (preg_match('/^(\w+:)?\/\//', $link) > 0) {
            return $link;
        }
        if ($link[ 0 ] === '/') {
            return $root . $link;
        }
        $path = explode('/', $parsedUrl[ 'path' ]);
        return $root . '/' . join('/', array_slice($path, 0, -1)) . '/' . $link;
    }
}