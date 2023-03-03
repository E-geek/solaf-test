<?php

namespace Runner;

use Car;
use Connector\EntityManager;
use Connector\MQ;
use Lib\CardGetter;

require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . '/../connector/db.php';
require_once __DIR__ . '/../connector/mq.php';
require_once __DIR__ . '/../lib/CardGetter.php';

class CardStorage {
    private CardGetter $cardGetter;

    /**
     * @var \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    private $carRepo;

    public function __construct(string $targetUrl, array $scheme) {
        $this->cardGetter = new CardGetter($targetUrl, $scheme);
        $this->carRepo = EntityManager::get()->getRepository('Car');
    }

    private static function _setupDownloadToStorageTask(string $link) {
        if (strlen($link) === 0) {
            return;
        }
        $url = parse_url($link);
        $folders = explode('/', $url[ 'path' ]);
        $filename = implode('/', array_slice($folders, -4));
        MQ::getInstance()->publish('image-link', [
            'type' => 'link',
            'link' => $link,
            'filename' => $filename,
        ]);
    }

    public function loadAndSave() {
        $carCards = $this->cardGetter->getAllCarDTO();
        if ($carCards === false) {
            echo 'Failed store, finish';
            return false;
        }
        foreach ($carCards as $key => $carDTO) {
            if (!$carDTO[ 'existsId' ]) {
                echo "Skip $key card because existsId not defined" . PHP_EOL;
                continue;
            }
            $exists = $this->carRepo->count([
                'existsId' => $carDTO[ 'existsId' ],
            ]);
            if ($exists !== 0) {
                echo "Skip " . $carDTO[ 'existsId' ] . PHP_EOL;
                continue;
            }
            if ($carDTO !== null && is_array($carDTO[ 'preview' ])) {
                foreach ($carDTO[ 'preview' ] as $link) {
                    self::_setupDownloadToStorageTask($link);
                }
            }
            $car = new Car($carDTO);
            EntityManager::get()->persist($car);
        }
        EntityManager::get()->flush();
        EntityManager::get()->close();
        return true;
    }
}