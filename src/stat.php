<?php
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . '/db.php';

use \DB\Tool as DB;
use Doctrine\ORM\EntityManagerInterface;

function getSqlResult(string $sql, EntityManagerInterface $em, array $params = []) {
    foreach ($params as $param) {
        $sql = preg_replace('/\?/', "$param", $sql, 1);
    }
    $stmt = $em->getConnection()->prepare($sql);
    $query = $stmt->executeQuery();
    $result = $query->fetchAllAssociative();
    return $result;
}

$query = <<<EOF
SELECT
	AVG(CAST(regexp_replace("mileage", '\D', '', 'g') AS integer)) as average_mileage,
	MAX(CAST(substring("power", '^\d+') AS integer)) as max_power,
	MIN(CAST(substring("power", '^\d+') AS integer)) as min_power
FROM "car";
EOF;

$data = getSqlResult($query, DB::getInstance());
extract($data[0]);
$average_mileage = round($average_mileage) . ' KM';

function getUniqValues(string $field, $limit = 0) :string {
    $query = <<<EOF
        SELECT 
          ?,
          COUNT("id") as "cnt"
        FROM "car"
        GROUP BY ?
        ORDER BY "cnt" DESC
    EOF;
    $params = ['"'.$field.'"', '"'.$field.'"'];
    if ($limit > 0) {
        $query .= ' LIMIT ?';
        $params[] = $limit;
    }
    $data = getSqlResult($query, DB::getInstance(), $params);
    $list = [];
    foreach ($data as $row) {
        $list[] = $row[$field];
    }
    return implode(', ', $list);
}

$countries = getUniqValues('origin', 3);
$fuels = getUniqValues('fuel');
$gearboxes = getUniqValues('gearbox');

echo <<<EOF
Summary data:
Average Mileage: $average_mileage
Most common Country of origin (top 3): ${countries}
Maximum Power (Hp): $max_power
Minimum Power (Hp): $min_power
All available options of Gearbox: $gearboxes
All available options of Fuel: $fuels

EOF;
