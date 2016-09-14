<?php

namespace unit\Kiboko\Component\BatchORM\Repository;
use Kiboko\Component\BatchORM\Repository\QueryBuilder\MySQL\FlatFileQueryBuilder;
use PHPUnit_Extensions_Database_TestCase as TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use unit\Kiboko\Component\BatchORM\TestCaseTrait;

class MySQLFlatFileRepositoryTest extends TestCase
{
    use TestCaseTrait;

    public function setUp()
    {
        parent::setUp();
        $this->getPdoConnection()->exec('SET SESSION sql_mode="NO_AUTO_VALUE_ON_ZERO"');
    }

    public function tearDown()
    {
        $this->getPdoConnection()->exec('SET SESSION sql_mode="NO_AUTO_VALUE_ON_ZERO"');
        parent::tearDown();
    }

    /**
     * Returns the database operation executed in test setup.
     *
     * @return \PHPUnit_Extensions_Database_Operation_IDatabaseOperation
     */
    protected function getSetUpOperation()
    {
        return \PHPUnit_Extensions_Database_Operation_Factory::CLEAN_INSERT();
    }

    /**
     * Returns the database operation executed in test cleanup.
     *
     * @return \PHPUnit_Extensions_Database_Operation_IDatabaseOperation
     */
    protected function getTearDownOperation()
    {
        //return \PHPUnit_Extensions_Database_Operation_Factory::TRUNCATE(true);
        return \PHPUnit_Extensions_Database_Operation_Factory::NONE();
    }

    /**
     * @return \PHPUnit_Extensions_Database_DataSet_ArrayDataSet
     */
    protected function getDataSet()
    {
        $table = new \PHPUnit_Extensions_Database_DataSet_DefaultTable(
            new \PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData(
                'testing_insert_table',
                [
                    'id', 'name', 'height', 'unit', 'date'
                ],
                [
                    'id'
                ]
            )
        );

        $table->addRow(
            [
                'id'     => 1,
                'name'   => 'John P. Doe',
                'height' => 9.,
                'unit'   => 'FEET',
                'date'   => '2016-01-02 03:03:05'
            ]
        );
        $table->addRow(
            [
                'id'     => 2,
                'name'   => 'Kathrine Switzer',
                'height' => 1.75,
                'unit'   => 'METER',
                'date'   => '2016-01-12 14:45:50'
            ]
        );
        $table->addRow(
            [
                'id'     => 3,
                'name'   => 'Anna Fischer',
                'height' => 1.45,
                'unit'   => 'METER',
                'date'   => '2016-03-12 14:45:50'
            ]
        );
        $table->addRow(
            [
                'id'     => 4,
                'name'   => 'Sabiha Gökçen',
                'height' => 1.47,
                'unit'   => 'METER',
                'date'   => '2016-03-31 08:10:23'
            ]
        );
        $table->addRow(
            [
                'id'     => 5,
                'name'   => 'Simone Segouin',
                'height' => 164,
                'unit'   => 'CENTIMETER',
                'date'   => '2016-04-07 14:23:17'
            ]
        );

        $dataSet = new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
        $dataSet->addTable($table);
        return $dataSet;
    }

    public function testLoadInexistent()
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)
            ->setMethods(
                [
                    'serialize',
                    'deserialize',
                ]
            )
            ->setConstructorArgs(
                [
                    $this->getDoctrineConnection()
                ]
            )
            ->getMock()
        ;

        $serializer->method('deserialize')
            ->willReturnCallback(function($data, $type, $format) {
                return json_decode(json_encode($data), false);
            })
        ;

        $queryBuilder = $this->getMockBuilder(FlatFileQueryBuilder::class)
            ->setMethods(
                [
                    'getSQL',
                    'getPath',
                ]
            )
            ->setConstructorArgs(
                [
                    $this->getDoctrineConnection()
                ]
            )
            ->getMock()
        ;

        $queryBuilder->method('getPath')->willReturn(__DIR__ . '/../data/' . uniqid('flat_file_repository', true) . '.csv');

        $sql =<<<SQL_EOF
SELECT 'id', 'name', 'height', 'unit', 'date' UNION ALL 
SELECT id, name, height, unit, date
INTO OUTFILE {$this->getDoctrineConnection()->quote($queryBuilder->getPath())}
CHARACTER SET 'UTF8'
FIELDS
    TERMINATED BY ','
    OPTIONALLY ENCLOSED BY '"'
    ESCAPED BY '\\\\'
LINES
    TERMINATED BY '\\n'
FROM testing_insert_table
WHERE id < 0
SQL_EOF;

        $queryBuilder->method('getSQL')->willReturn($sql);

        $repository = new \Kiboko\Component\BatchORM\Repository\MySQLFlatFileRepository(
            $this->getDoctrineConnection(),
            $serializer
        );

        $repository->apply($queryBuilder);

        foreach ($repository->walkResults(\stdClass::class) as $item) {
            $this->fail('Repository should not have fetched any item.');
        }

        $repository->cleanup();

        $this->assertFileNotExists($queryBuilder->getPath());
    }

    public function testLoadItems()
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)
            ->setMethods(
                [
                    'serialize',
                    'deserialize',
                ]
            )
            ->getMock()
        ;

        $serializer->method('deserialize')
            ->willReturnCallback(function($data, $type, $format) {
                return json_decode(json_encode($data), false);
            })
        ;

        $queryBuilder = $this->getMockBuilder(FlatFileQueryBuilder::class)
            ->setMethods(
                [
                    'getSQL',
                    'getPath',
                ]
            )
            ->setConstructorArgs(
                [
                    $this->getDoctrineConnection()
                ]
            )
            ->getMock()
        ;

        $queryBuilder->method('getPath')->willReturn(__DIR__ . '/../data/' . uniqid('flat_file_repository', true) . '.csv');

        $sql =<<<SQL_EOF
SELECT 'id', 'name', 'height', 'unit', 'date' UNION ALL 
SELECT id, name, height, unit, date
INTO OUTFILE {$this->getDoctrineConnection()->quote($queryBuilder->getPath())}
CHARACTER SET 'UTF8'
FIELDS
    TERMINATED BY ','
    OPTIONALLY ENCLOSED BY '"'
    ESCAPED BY '\\\\'
LINES
    TERMINATED BY '\\n'
FROM testing_insert_table
WHERE id <= 2
SQL_EOF;

        $queryBuilder->method('getSQL')->willReturn($sql);

        $repository = new \Kiboko\Component\BatchORM\Repository\MySQLFlatFileRepository(
            $this->getDoctrineConnection(),
            $serializer
        );

        $repository->apply($queryBuilder);

        foreach ($repository->walkResults(\stdClass::class) as $item) {
            $this->assertNotNull($item->id);
            $this->assertObjectHasAttribute('name', $item);
            $this->assertObjectHasAttribute('height', $item);
            $this->assertObjectHasAttribute('unit', $item);
            $this->assertObjectHasAttribute('date', $item);
        }

        $repository->cleanup();

        $this->assertFileNotExists($queryBuilder->getPath());
    }
}
