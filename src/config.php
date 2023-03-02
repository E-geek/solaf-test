<?php

use PHPHtmlParser\Dom\Node\HtmlNode;

$targetUrl = 'https://ecarstrade.com/auctions/stock/page1?sort=mark_model.asc';
$targetUrl = 'http://192.168.1.20:8080/index-min.html';

$scheme = [
    'title' => '.item-title > a > span',
    'link' => [
        'selector' => '.item-title > a[href]',
        'processor' => function(HtmlNode $node) :?string {
            global $targetUrl;
            return linkToURL($node->getAttribute('href'), $targetUrl);
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
        'processor' => function(HtmlNode $node) :?DateTime {
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
        'processor' => function(HtmlNode $node) :?string {
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
        'processor' => function(HtmlNode $node) :array {
            $photos = $node->find('.hover-photo');
            $preview = [];
            /** @var HtmlNode $photo */
            foreach ($photos as $photo) {
                global $targetUrl;
                $preview[] = linkToURL($photo->getAttribute('data-src'), $targetUrl);
            }
            return $preview;
        },
        'required' => false,
    ]
];