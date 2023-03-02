<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . '/db.php';
require __DIR__ . '/config.php';

use \PHPHtmlParser\Dom\Node\HtmlNode;
use \DB\Tool as DB;

function getNodeText(HtmlNode $node, string $selector): ?string {
    $result = $node->find($selector)[0];
    if ($result) {
        $text = trim($result->text);
        unset($result);
        return $text;
    }
    return null;
};

function linkToURL(string $link, string $base): string {
    $parsedUrl = parse_url($base);
    $root = ($parsedUrl['scheme'] ?? 'https') . '://'
        . $parsedUrl['host']
        . ($parsedUrl['port'] ? ':' . $parsedUrl['port'] : '');
    if (preg_match('/^(\w+:)?\/\//', $link) > 0) {
        return $link;
    }
    if ($link[0] === '/') {
        return $root . $link;
    }
    $path = explode('/', $parsedUrl['path']);
    return $root . '/' . join('/', array_slice($path, 0, -1)) . '/' . $link;
}

function parseCardByScheme(HtmlNode $carCard, $scheme): ?array {
    $result = [];
    foreach ($scheme as $propName => $description) {
        $required = true;
        $processor = false;
        $regexp = false;
        $default = null;
        if (is_string($description)) {
            $selector = $description;
        } else {
            extract($description);
            if ($default !== null) {
                $required = false;
            }
        }
        $nodeValue = getNodeText($carCard, $selector);
        if ($nodeValue === null) {
            if ($required) {
                echo "Not found $selector node for $propName prop" . PHP_EOL;
                return null;
            } else {
                $result[$propName] = $default ?? null;
                continue;
            }
        }
        if ($processor) {
            $node = $carCard->find($selector)[0];
            $result[$propName] = $processor($node);
            unset($node);
        } elseif ($regexp) {
            if (!preg_match($regexp, $nodeValue, $matches)) {
                echo "Cannot parse $selector node for $propName prop can't match $option" . PHP_EOL;
                return null;
            };
            $result[$propName] = $matches[1];
        } else {
            $result[$propName] = $nodeValue;
        }
    }
    return $result;
}

function process() {
    global $targetUrl, $scheme;
    $ch = curl_init();
    echo "Start to download..." . PHP_EOL;
    curl_setopt_array($ch, [
        CURLOPT_URL => $targetUrl,
        CURLOPT_ACCEPT_ENCODING => "",
        CURLOPT_HTTPHEADER => [
            "Accept: text/html,application/xhtml+xml",
            "Accept-Encoding: gzip, deflate",
            "Cookie: eCT/LANG=en; eCT/first_user_request=%5B%5D; eCT/perpage=400",
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_FORBID_REUSE => 1,
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    if (curl_error($ch)) {
        echo "Get list faults with error: " . curl_error($ch) . PHP_EOL;
        exit(1);
    }
    echo "Complete." . PHP_EOL;
    echo "Parsing..." . PHP_EOL;
    $dom = new \PHPHtmlParser\Dom();
    try {
        $dom->loadStr($body);
        $counterNode = $dom->find('li.nav-item.divider.active span.nav-counter.top');
        if ($counterNode->count() !== 1) {
            echo "Page template was changes. Stop processing" . PHP_EOL;
            exit(2);
        }
        $count = +$counterNode[0]->text;
        unset($counterNode);
        if ($count > 400) {
            echo "Not implemented yet load more than 400 items" . PHP_EOL;
            exit(4);
        }
        $carCards = $dom->find('.car-item');
        if ($carCards->count() !== $count) {
            echo "Count cards on page and in counter is different. Behavior undefined. " .
                "Cards: " . $carCards->count() . " Count expected: $count" . PHP_EOL;
            exit(5);
        }
    } catch (Throwable $err) {
        echo "Parsing has error: " . $err . PHP_EOL;
        exit(3);
    }
    echo "Found ${count} card of the car. Complete." . PHP_EOL;
    echo "Structuring and store" . PHP_EOL;

    $carRepo = DB::getInstance()->getRepository('Car');

    /** @var HtmlNode $carCard */
    foreach ($carCards as $key => $carCard) {
        $carDTO = parseCardByScheme($carCard, $scheme);
        if ($carDTO === null || !$carDTO['existsId']) {
            echo "Skip $key card" . PHP_EOL;
            continue;
        }
        $exists = $carRepo->findOneBy([
            'existsId' => $carDTO['existsId'],
        ]);
        if ($exists !== null) {
            echo "Skip " . $carDTO['existsId'] . PHP_EOL;
            continue;
        }
        $car = new Car($carDTO);
        DB::getInstance()->persist($car);
        DB::getInstance()->flush();
    }
    unset($carCards);
    echo "Done." . PHP_EOL;
    DB::getInstance()->getConnection()->close();
}

process();
