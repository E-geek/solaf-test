<?php

namespace Lib;

require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/Loader.php";

use \PHPHtmlParser\Dom\Node\HtmlNode;
use \PHPHtmlParser\Dom;
use PHPHtmlParser\Exceptions\ChildNotFoundException;
use PHPHtmlParser\Exceptions\CircularException;
use PHPHtmlParser\Exceptions\ContentLengthException;
use PHPHtmlParser\Exceptions\LogicalException;
use PHPHtmlParser\Exceptions\NotLoadedException;
use PHPHtmlParser\Exceptions\StrictException;

class CardGetter {
    private Dom $dom;

    private string $targetUrl;

    private array $scheme;

    private Loader $loader;

    private array $carCards;

    public function __construct(string $targetUrl, array $scheme) {
        $this->dom = new Dom();
        $this->targetUrl = $targetUrl;
        $this->scheme = $scheme;
        $this->loader = new Loader();
    }

    private function _loadDocument($url) :bool {
        try {
            echo "Start to download..." . PHP_EOL;
            $html = $this->loader->fetch($url);
            echo "Complete download." . PHP_EOL;
            echo "Parsing..." . PHP_EOL;
            $this->dom->loadStr($html);
        } catch (FetchException $e) {
            echo "Download error: " . $e . PHP_EOL;
            return false;
        } catch (
        ChildNotFoundException
        |CircularException
        |StrictException
        |ContentLengthException
        |LogicalException $e) {
            echo "Parse error: " . $e . PHP_EOL;
            return false;
        }
        return true;
    }

    /**
     * @return false|int
     * @throws ChildNotFoundException
     * @throws NotLoadedException
     */
    private function _getCount() {
        $counterNode = $this->dom->find('li.nav-item.divider.active span.nav-counter.top');
        if ($counterNode->count() !== 1) {
            echo "Page template was changes. Stop processing" . PHP_EOL;
            return false;
        }
        $counter = intval($counterNode[ 0 ]->text);
        return $counter;
    }

    private function _loadAllNextPages(int $countCards, int $countCardsOnPage) :bool {
        $countCardsOnPage = sizeof($this->carCards);
        if ($countCardsOnPage !== 100) {
            echo "Unexpected behavior. First page is not a last and return less than 100 cards " . PHP_EOL;
        }
        $pages = ceil($countCards / 100);
        for ($page = 2; $page <= $pages; $page++) {
            echo "Processing ${page} page..." . PHP_EOL;
            $url = str_replace('[X]', $page, $this->targetUrl);
            $success = $this->_loadDocument($url);
            if (!$success) {
                return false;
            }
            $carCards = $this->dom->find('.car-item');
            $this->carCards = array_merge($this->carCards, $carCards->toArray());
            echo "Done" . PHP_EOL;
        }
        return true;
    }

    private function _firstDownload() :bool {
        echo "Processing 1 page..." . PHP_EOL;
        $url = str_replace('[X]', '1', $this->targetUrl);
        $success = $this->_loadDocument($url);
        if (!$success) {
            return false;
        }
        $countCards = $this->_getCount();
        if ($countCards === false) {
            return false;
        }
        $carCards = $this->dom->find('.car-item');
        $this->carCards = $carCards->toArray();
        echo "Done" . PHP_EOL;
        $countCardsOnPage = $carCards->count();
        if ($countCardsOnPage < $countCards) {
            $loaded = $this->_loadAllNextPages($countCards, $countCardsOnPage);
            if (!$loaded) {
                echo "Loading next pages  has fault" . PHP_EOL;
                return false;
            }
        }
        if ($countCards !== sizeof($this->carCards)) {
            echo "After loading count of cards mismatch: expected ${carCards} but got " . sizeof($this->carCards) . PHP_EOL;
            return false;
        }
        return true;
    }

    public function getAllCarDTO() {
        $success = $this->_firstDownload();
        if (!$success) {
            echo "Download all car cards has fault" . PHP_EOL;
            return false;
        }
        $result = [];
        foreach ($this->carCards as $key => $carCard) {
            $carCard = self::parseCardByScheme($carCard, $this->scheme);
            if (!isset($carCard)) {
                continue;
            }
            $result[] = $carCard;
        }
        return $result;
    }

    private static function getNodeText(HtmlNode $node, string $selector) :?string {
        $result = $node->find($selector)[ 0 ];
        if ($result) {
            $text = trim($result->text);
            unset($result);
            return $text;
        }
        return null;
    }

    public static function parseCardByScheme(HtmlNode $carCard, $scheme) :?array {
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
            $nodeValue = self::getNodeText($carCard, $selector);
            if ($nodeValue === null) {
                if ($required) {
                    echo "Not found $selector node for $propName prop" . PHP_EOL;
                    return null;
                } else {
                    $result[ $propName ] = $default ?? null;
                    continue;
                }
            }
            if ($processor) {
                $node = $carCard->find($selector)[ 0 ];
                $result[ $propName ] = $processor($node);
                unset($node);
            } elseif ($regexp) {
                if (!preg_match($regexp, $nodeValue, $matches)) {
                    echo "Cannot parse $selector node for $propName prop can't match $option" . PHP_EOL;
                    return null;
                };
                $result[ $propName ] = $matches[ 1 ];
            } else {
                $result[ $propName ] = $nodeValue;
            }
        }
        return $result;
    }
}
