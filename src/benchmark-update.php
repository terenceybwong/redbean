<?php
/**
 * Rough performance benchmark test on RedBean update.
 */
use \RedBeanPHP\R;
use \RedBeanPHP\ToolBox;

require_once __DIR__ . '/../vendor/autoload.php';

const NUM_RECORDS = 100000;
const TABLE_NAME = 't';

/**
 * Pre-requisite:
 * - Database preparation
 * - MySQL version 5.7
 *
 * SQL to prepare database:
 *   CREATE DATABASE redbean;
 *   USE redbean;
 *   CREATE USER redbean IDENTIFIED BY 'redbean';
 *   GRANT ALL ON redbean.* to redbean;
 *   FLUSH PRIVILEGES;
 */
/** @var ToolBox $db */
$db = R::setup('mysql:dbname=redbean', 'redbean', 'redbean');
/*
 * R::findAll() resulted in memory exhaustion
 * $rows = R::findAll(TABLE_NAME);
 *
 * R::count() can be used instead
 * $sql = sprintf('SELECT COUNT(id) AS num FROM %s', TABLE_NAME);
 * $result = $db->getDatabaseAdapter()->get($sql);
 */
$recordCount = R::count(TABLE_NAME);

$numPending = NUM_RECORDS - $recordCount;
if ($numPending > 0) {
    generateRows(array_slice(generateData(), 0, $numPending));
}

//readData();
updateRowsByRedBean(generateData(), false);
updateRowsByRedBean(generateData(), true);
updateRowsByRedBean(generateData(), false, true);
updateRowsByRedBean(generateData(), true, true);
updateRowsBySQL($db, generateData(), false);
updateRowsBySQL($db, generateData(), true);

R::close();

/**
 * @return array
 *
 * @throws Exception
 */
function generateData()
{
    $data = [];
    $count = 0;
    while (++$count <= NUM_RECORDS) {
        $data[] = [
            bin2hex(random_bytes(10)),
            bin2hex(random_bytes(10)),
            bin2hex(random_bytes(10))
        ];
    }

    return $data;
}

/**
 * @param array $data
 */
function generateRows(array $data)
{
    printf("Creating %s records\n", number_format(count($data)));

    $ts = microtime(true);
    R::begin();
    foreach ($data as $datum) {
        $row = R::dispense(TABLE_NAME);
        $row->a = $datum[0];
        $row->b = $datum[0];
        $row->c = $datum[0];
        R::store($row);
    }
    R::commit();

    printf("===> Elapsed time: %s seconds\n", number_format(microtime(true) - $ts, 2));
}

/**
 * @param array     $data
 * @param bool|null $transaction
 * @param bool|null $freeze
 */
function updateRowsByRedBean(array $data, bool $transaction = null, bool $freeze = null)
{
    printf(
        "Updating %s records by RedBean%s%s\n",
        number_format(count($data)),
        $transaction === true ? ' in transaction' : '',
        $freeze === true ? ' with frozen schema' : ''
    );

    if ($freeze === true) {
        R::freeze();
    }
    if ($transaction === true) {
        R::begin();
    }

    $id = 0;
    $ts = microtime(true);
    foreach ($data as $datum) {
        $row = R::load(TABLE_NAME, $id++);
        $row->a = $datum[0];
        $row->b = $datum[1];
        $row->c = $datum[2];
        R::store($row);
    }
    $elapsed = microtime(true) - $ts;
    printf("===> Elapsed time (prepare rows): %s seconds\n", number_format($elapsed, 2));

    if ($transaction === true) {
        R::commit();
    }
    $total = microtime(true) - $ts;
    $elapsed = $total - $elapsed;

    printf("===> Elapsed time (commit): %s seconds\n", number_format($elapsed, 2));
    printf("===> Elapsed time (total): %s seconds\n", number_format($total, 2));
}

/**
 * @param ToolBox   $db
 * @param array     $data
 * @param bool|null $transaction
 */
function updateRowsBySQL(ToolBox $db, array $data, bool $transaction = null)
{
    printf(
        "Updating %s records by SQL%s\n",
        number_format(count($data)),
        $transaction === true ? ' in transaction' : ''
    );

    if ($transaction === true) {
        $db->getDatabaseAdapter()->exec('START TRANSACTION');
    }
    $id = 0;
    $ts = microtime(true);
    foreach ($data as $datum) {
        $sql = sprintf(
            'UPDATE %s SET a="%s", b="%s", c="%s" WHERE id="%s"',
            TABLE_NAME,
            $datum[0],
            $datum[1],
            $datum[2],
            $id++
        );
        $db->getDatabaseAdapter()->exec($sql);
    }
    $elapsed = microtime(true) - $ts;
    printf("===> Elapsed time (prepare rows): %s seconds\n", number_format($elapsed, 2));

    if ($transaction === true) {
        $db->getDatabaseAdapter()->exec('COMMIT');
    }
    $total = microtime(true) - $ts;
    $elapsed = $total - $elapsed;

    printf("===> Elapsed time (commit): %s seconds\n", number_format($elapsed, 2));
    printf("===> Elapsed time (total): %s seconds\n", number_format($total, 2));
}

function readData()
{
    printf("Reading data\n");

    $ts = microtime(true);
    $collection = R::findCollection(TABLE_NAME, 'ORDER BY id ASC');
    while ($item = $collection->next()) {
        printf("%d, ", $item->id);
    }
    print "\n";

    printf("===> Elapsed time: %s seconds\n", number_format(microtime(true) - $ts));
}
