<?php

namespace Tests;

use Tool\URL;
use PHPUnit\Framework\TestCase;

class URLTest extends TestCase {
    public function testLinkToURL() {
        $this->assertEquals('https://base.com/some/link', URL::linkToURL(
            'some/link',
            'https://base.com/'
        ));
        $this->assertEquals('https://base.com/some/link', URL::linkToURL(
            '/some/link',
            'https://base.com/'
        ));
        $this->assertEquals('https://base.com/some/link', URL::linkToURL(
            'link',
            'https://base.com/some/'
        ));
        $this->assertEquals('https://base.com/some/link', URL::linkToURL(
            '/some/link',
            'https://base.com/other/path/'
        ));
        $this->assertEquals('https://ecarstrade.com/thumbnails/carsphotos/3260001-3270000/3263809/photo_044/260x0__r.jpg', URL::linkToURL(
            '/thumbnails/carsphotos/3260001-3270000/3263809/photo_044/260x0__r.jpg',
            'https://ecarstrade.com/auctions/stock/page1?sort=mark_model.asc'
        ));
    }
}
