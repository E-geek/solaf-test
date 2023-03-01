<?php
require_once __DIR__ . "/vendor/autoload.php";

use \PHPHtmlParser\Dom\Node\HtmlNode;
use \PHPHtmlParser\Dom\Node\Collection;

$targetUrl = 'https://ecarstrade.com/auctions/stock/page1?sort=mark_model.asc';
$targetUrl = 'http://192.168.1.20:3000/index.html';

$ch = curl_init();
echo "Start to download...";
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
    echo "Get list faults with error: " . curl_error($ch);
    exit(1);
}
echo "Complete.";
echo "Parsing...";
$dom = new \PHPHtmlParser\Dom();
try {
    $dom->loadStr($body);
    $counterNode = $dom->find('li.nav-item.divider.active span.nav-counter.top');
    if($counterNode->count() !== 1) {
        echo "Page template was changes. Stop processing";
        exit(2);
    }
    $count = +$counterNode[0]->text;
    if ($count > 400) {
        echo "Not implemented yet load more than 400 items";
        exit(4);
    }
    $carCards = $dom->find('.car-item');
    if ($carCards->count() !== $count) {
        echo "Count cards on page and in counter is different. Behavior undefined. ".
        "Cards: " . $carCards->count() . " Count expected: $count";
        exit(5);
    }
} catch (Throwable $err) {
    echo "Parsing has error: " . $err;
    exit(3);
}
echo "Found ${count} card of the car. Complete.";
echo "Structuring and store";
function getNodeText(HtmlNode $node, string $selector): ?string {
    $result = $node->find($selector)[0];
    if ($result) {
        return trim($result->text);
    }
    return null;
};

function linkToURL(string $link, string $base) :string {
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
    return $root .'/' . join('/', array_slice($path, 0, -1)) . '/' . $link;
}

$scheme = [
    [ '.item-title > a > span', 'title' ],
    [ '.item-title > a[href]', 'link', function(HtmlNode $node) :?string {
        global $targetUrl;
        return linkToURL($node->getAttribute('href'), $targetUrl);
    } ],
    [ '.item-title .text-muted', 'id', '/#(\d+)/' ],
    [ '.item-title .text-muted', 'brand-model', '/-\s*(\S.*\S)/' ],
    [ '[data-original-title="First registration date"]', 'reg-date' ],
    [ '[data-original-title="Mileage"]', 'mileage' ],
    [ '[data-original-title="Gearbox"]', 'gearbox' ],
    [ '[data-original-title="Fuel"]', 'fuel' ],
    [ '[data-original-title="Engine size"]', 'engine-size' ],
    [ '[data-original-title="Power"]', 'power' ],
    [ '[data-original-title="Emission Class"]', 'emission-class' ],
    [ '[data-original-title^="CO"]', 'emission-class' ],
    [ '[data-original-title="Car location"]', 'location' ],
    [ '[data-original-title^="Country of origin:"]', 'origin', function(HtmlNode $node) :?string {
        $title = $node->getAttribute('data-original-title');
        if (!$title) {
            return null;
        }
        return trim(str_replace('Country of origin:', '', $title));
    } ],
    [ '.hover-photos', 'preview', function(HtmlNode $node) :array {
        $photos = $node->find('.hover-photo');
        $preview = [];
        /** @var HtmlNode $photo */
        foreach ($photos as $photo) {
            global $targetUrl;
            $preview[] = linkToURL($photo->getAttribute('data-src'), $targetUrl);
        }
        return $preview;
    }]
];

function parseCardByScheme(HtmlNode $carCard, $scheme) :?array {
    $result = [];
    foreach ($scheme as $property) {
        [$selector, $propName] = $property;
        $nodeValue = getNodeText($carCard, $selector);
        if ($nodeValue === null) {
            echo "Not found $selector node for $propName prop";
            return null;
        }
        if (sizeof($property) === 2) {
            $result[$propName] = $nodeValue;
        } else {
            $option = $property[2];
            if (is_string($option)) {
                if (!preg_match($option, $nodeValue, $matches)) {
                    echo "Cannot parse $selector node for $propName prop can't match $option";
                    return null;
                };
                $result[$propName] = $matches[1];
            } else {
                $node = $carCard->find($selector)[0];
                $result[$propName] = $option($node);
            }
        }
        
    }
    return $result;
} 

/** @var HtmlNode $carCard */
foreach ($carCards as $key => $carCard) {
    var_dump(parseCardByScheme($carCard, $scheme));
    break;
}