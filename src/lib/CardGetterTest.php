<?php

namespace Tests;

use Config;
use Lib\CardGetter;
use PHPHtmlParser\Dom;
use PHPUnit\Framework\TestCase;

class CardGetterTest extends TestCase {

    private string $reqBody;

    private string $carItemHTML;

    private array $schema;

    public function setUp() :void {
        parent::setUp();
        $this->reqBody = (string) file_get_contents(__DIR__ . '/../mock-data/index-min.html');
        $this->carItemHTML = (string) file_get_contents(__DIR__ . '/../mock-data/car-item.html');
        $this->schema = Config::getScheme();
    }

    public function test__construct() {
        $cardGetter = new CardGetter('http://me.me/', $this->schema);
        $this->assertInstanceOf('Lib\CardGetter', $cardGetter);
    }

    public function testGetAllCarDTO() {
        $loader = $this->createStub(\Lib\Loader::class);
        $loader
            ->method('fetch')
            ->willReturn($this->reqBody);
        $cardGetter = new CardGetter('http://localhost/index[X].html', $this->schema);
        $cardGetter->setLoader($loader);;
        $cards = $cardGetter->getAllCarDTO();
        $this->assertEquals(10, sizeof($cards));
    }

    public function testParseCardByScheme() {
        $dom = new Dom();
        $dom->loadStr($this->carItemHTML);
        $node = $dom->find('.car-item')[ 0 ];
        $dto = CardGetter::parseCardByScheme($node, $this->schema);
        // check values only in test task
        $this->assertEquals("83 850 KM", $dto[ 'mileage' ]);
        $this->assertEquals("Belgium", $dto[ 'origin' ]);
        $this->assertEquals("150 Hp (110 kW)", $dto[ 'power' ]);
        $this->assertEquals("Manual", $dto[ 'gearbox' ]);
        $this->assertEquals("Diesel", $dto[ 'fuel' ]);
        $this->assertEquals(5, sizeof($dto[ 'preview' ]));
        $this->assertEquals('https://ecarstrade.com/thumbnails/carsphotos/3510001-3520000/3510642/photo_000/260x0__r.jpg', $dto[ 'preview' ][0]);
    }
}
