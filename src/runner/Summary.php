<?php

namespace Runner;

require_once __DIR__ . "/../../vendor/autoload.php";

use Doctrine\ORM\EntityManagerInterface;
use Connector\EntityManager;

class Summary {
    private \Doctrine\ORM\EntityManager $em;

    public function __construct() {
        $this->em = EntityManager::get();
    }

    private function _getSqlResult(string $sql, array $params = []) {
        foreach ($params as $param) {
            $sql = preg_replace('/\?/', "$param", $sql, 1);
        }
        $stmt = $this->em->getConnection()->prepare($sql);
        $query = $stmt->executeQuery();
        $result = $query->fetchAllAssociative();
        return $result;
    }

    private function _getAvgMielageAndMinMaxPower() :array {
        $query = <<<EOF
            SELECT
                AVG(CAST(regexp_replace("mileage", '\D', '', 'g') AS integer)) as average_mileage,
                MAX(CAST(substring("power", '^\d+') AS integer)) as max_power,
                MIN(CAST(substring("power", '^\d+') AS integer)) as min_power
            FROM "car";
        EOF;
        $data = $this->_getSqlResult($query);
        return [
            $data[ 0 ][ 'average_mileage' ],
            $data[ 0 ][ 'max_power' ],
            $data[ 0 ][ 'min_power' ],
        ];
    }

    private function _getUniqValues(string $field, $limit = 0) :string {
        $query = <<<EOF
            SELECT 
              ?,
              COUNT("id") as "cnt"
            FROM "car"
            GROUP BY ?
            ORDER BY "cnt" DESC
        EOF;
        $params = ['"' . $field . '"', '"' . $field . '"'];
        if ($limit > 0) {
            $query .= ' LIMIT ?';
            $params[] = $limit;
        }
        $data = $this->_getSqlResult($query, $params);
        $list = [];
        foreach ($data as $row) {
            $list[] = $row[ $field ];
        }
        return implode(', ', $list);
    }

    public function getSummary() {
        [$average_mileage, $max_power, $min_power] = $this->_getAvgMielageAndMinMaxPower();
        $average_mileage = round($average_mileage);
        $countries = $this->_getUniqValues('origin', 3);
        $fuels = $this->_getUniqValues('fuel');
        $gearboxes = $this->_getUniqValues('gearbox');

        echo <<<EOF
            Summary data:
            Average Mileage: ${average_mileage}
            Most common Country of origin (top 3): ${countries}
            Maximum Power (Hp): ${max_power}
            Minimum Power (Hp): ${min_power}
            All available options of Gearbox: ${gearboxes}
            All available options of Fuel: ${fuels}
            
        EOF;
    }

}