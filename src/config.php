<?php

require_once __DIR__ . '/tool/URL.php';

use PHPHtmlParser\Dom\Node\HtmlNode;
use Tool\URL;

class Config {
    public static string $targetUrl = 'https://ecarstrade.com/auctions/stock/page[X]?sort=mark_model.asc';

    public static function getScheme() {
        return [
            'title' => '.item-title > a > span',
            'link' => [
                'selector' => '.item-title > a[href]',
                'processor' => function (HtmlNode $node) :?string {
                    return URL::linkToURL($node->getAttribute('href'), self::$targetUrl);
                },
                'required' => false,
            ],
            'existsId' => [
                'selector' => '.item-title .text-muted',
                'regexp' => '/#(\d+)/',
            ],
            'brand-model' => [
                'selector' => '.item-title .text-muted',
                'regexp' => '/-\s*(\S.*\S)/',
                'required' => false,
            ],
            'registered' => [
                'selector' => '[data-original-title="First registration date"]',
                'processor' => function (HtmlNode $node) :?DateTime {
                    $dateString = trim($node->text);
                    if (!$dateString) {
                        return null;
                    }
                    return DateTime::createFromFormat('d/m/Y', '15/' . $dateString, new DateTimeZone('CET'));
                },
                'required' => false,
            ],
            'mileage' => '[data-original-title="Mileage"]',
            'gearbox' => '[data-original-title="Gearbox"]',
            'fuel' => '[data-original-title="Fuel"]',
            'power' => '[data-original-title="Power"]',
            'engine-size' => [
                'selector' => '[data-original-title="Engine size"]'
            ],
            'emission-class' => [
                'selector' => '[data-original-title="Emission Class"]'
            ],
            'co2' => [
                'selector' => '[data-original-title^="CO"]'
            ],
            'location' => [
                'selector' => '[data-original-title="Car location"]'
            ],
            'origin' => [
                'selector' => '[data-original-title^="Country of origin:"]',
                'processor' => function (HtmlNode $node) :?string {
                    $title = $node->getAttribute('data-original-title');
                    if (!$title) {
                        return null;
                    }
                    return trim(str_replace('Country of origin:', '', $title));
                },
                'default' => 'unknown',
            ],
            'preview' => [
                'selector' => '.hover-photos',
                'processor' => function (HtmlNode $node) :array {
                    $photos = $node->find('.hover-photo');
                    $preview = [];
                    /** @var HtmlNode $photo */
                    foreach ($photos as $photo) {
                        $preview[] = URL::linkToURL($photo->getAttribute('data-src'), self::$targetUrl);
                    }
                    return $preview;
                },
                'required' => false,
            ]
        ];
    }
}