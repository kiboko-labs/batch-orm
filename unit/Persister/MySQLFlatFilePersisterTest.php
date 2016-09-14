<?php

namespace unit\Kiboko\Component\BatchORM;

use Kiboko\Component\BatchORM\Persister\FlatFilePersisterInterface;
use Kiboko\Component\BatchORM\MySQLConfigurationManager;
use Kiboko\Component\BatchORM\Persister\AbstractMySQLFlatFilePersister;
use Kiboko\Component\BatchORM\Persister\QueryBuilder\MySQL\FlatFileQueryBuilder;
use Kiboko\Component\BatchORM\Persister\UnitOfWork\UnitOfWorkInterface;
use PHPUnit_Extensions_Database_TestCase as TestCase;

class MySQLFlatFilePersisterTest extends TestCase
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
        return \PHPUnit_Extensions_Database_Operation_Factory::TRUNCATE(true);
    }

    /**
     * @return \PHPUnit_Extensions_Database_DataSet_ArrayDataSet
     */
    protected function getDataSet()
    {
        $dataSet = new \PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
        $dataSet->addTable(
            new \PHPUnit_Extensions_Database_DataSet_DefaultTable(
                new \PHPUnit_Extensions_Database_DataSet_DefaultTableMetaData(
                    'testing_insert_table',
                    [
                        'id', 'name', 'height', 'unit', 'date'
                    ],
                    [
                        'id'
                    ]
                )
            )
        );
        return $dataSet;
    }
    
    public function testInsertingNone()
    {
        $queryBuilder = $this->getMockBuilder(FlatFileQueryBuilder::class)
            ->setConstructorArgs([$this->getDoctrineConnection()])
            ->setMethods(
                [
                    'getSQL',
                    'getPath',
                ]
            )
            ->getMock()
        ;

        $sql =<<<SQL_EOF
LOAD DATA INFILE {$this->getDoctrineConnection()->quote(__DIR__ . '/../data/empty_flat_file.csv')}
REPLACE INTO TABLE testing_insert_table
CHARACTER SET 'UTF8'
FIELDS
    TERMINATED BY ','
    OPTIONALLY ENCLOSED BY '"'
    ESCAPED BY '\\\\'
LINES
    TERMINATED BY '\\n'
(id, name, height, unit, date)
SQL_EOF;

        $queryBuilder->method('getSQL')->willReturn($sql);
        $queryBuilder->method('getPath')->willReturn(__DIR__ . '/../data/empty_flat_file.csv');

        $unitOfWork = $this->getMockForAbstractClass(
            UnitOfWorkInterface::class, [], '', false, false, true,
            [
                'enqueue',
                'getIterator'
            ]
        );

        $unitOfWork->method('enqueue');

        $unitOfWork->method('getIterator')->willReturn(new \ArrayIterator([]));

        /** @var FlatFilePersisterInterface $persister */
        $persister = $this->getMockForAbstractClass(
            AbstractMySQLFlatFilePersister::class,
            [
                $this->getDoctrineConnection(),
                $queryBuilder,
                $unitOfWork,
                new MySQLConfigurationManager($this->getDoctrineConnection())
            ]
        );

        foreach ($persister->flush() as $newItem) {
            $this->fail('No items should be returned on an empty set.');
        }

        $expected = $this->getDataSet();

        $actual = new \PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
        $actual->addTable('testing_insert_table');

        $this->assertDataSetsEqual($expected, $actual);
    }

    public function testInsertingWhileEmpty()
    {
        $queryBuilder = $this->getMockBuilder(FlatFileQueryBuilder::class)
            ->setConstructorArgs([$this->getDoctrineConnection()])
            ->setMethods(
                [
                    'getSQL',
                    'getPath',
                ]
            )
            ->getMock()
        ;

        $sql =<<<SQL_EOF
LOAD DATA INFILE {$this->getDoctrineConnection()->quote(__DIR__ . '/../data/flat_file_persister.csv')}
REPLACE INTO TABLE testing_insert_table
CHARACTER SET 'UTF8'
FIELDS
    TERMINATED BY ','
    OPTIONALLY ENCLOSED BY '"'
    ESCAPED BY '\\\\'
LINES
    TERMINATED BY '\\n'
(id, name, height, unit, date)
SQL_EOF;

        $queryBuilder->method('getSQL')->willReturn($sql);
        $queryBuilder->method('getPath')->willReturn(__DIR__ . '/../data/flat_file_persister.csv');

        $unitOfWork = $this->getMockForAbstractClass(
            UnitOfWorkInterface::class, [], '', false, false, true,
            [
                'enqueue',
                'getIterator'
            ]
        );

        $unitOfWork->method('enqueue');

        $unitOfWork->method('getIterator')->willReturn(new \ArrayIterator(
            [
                [
                    'id'     => 1,
                    'name'   => 'John P. Doe',
                    'height' => 9.,
                    'unit'   => 'FEET',
                    'date'   => '2016-01-02 03:03:05'
                ],
                [
                    'id'     => 2,
                    'name'   => 'Kathrine Switzer',
                    'height' => 1.75,
                    'unit'   => 'METER',
                    'date'   => '2016-01-12 14:45:50'
                ],
                [
                    'id'     => 3,
                    'name'   => 'Anna Fischer',
                    'height' => 1.45,
                    'unit'   => 'METER',
                    'date'   => '2016-03-12 14:45:50'
                ],
                [
                    'id'     => 4,
                    'name'   => 'Sabiha Gökçen',
                    'height' => 1.47,
                    'unit'   => 'METER',
                    'date'   => '2016-03-31 08:10:23'
                ],
                [
                    'id'     => 5,
                    'name'   => 'Simone Segouin',
                    'height' => 164,
                    'unit'   => 'CENTIMETER',
                    'date'   => '2016-04-07 14:23:17'
                ],
            ]
        ));

        /** @var FlatFilePersisterInterface $persister */
        $persister = $this->getMockForAbstractClass(
            AbstractMySQLFlatFilePersister::class,
            [
                $this->getDoctrineConnection(),
                $queryBuilder,
                $unitOfWork,
                new MySQLConfigurationManager($this->getDoctrineConnection())
            ]
        );

        $index = 0;
        foreach ($persister->flush() as $newItem) {
            $this->assertInternalType('array', $newItem);
            $this->assertArrayHasKey('id', $newItem);
            $this->assertEquals(++$index, $newItem['id']);
        }

        $expected = new \PHPUnit_Extensions_Database_DataSet_ArrayDataSet(
            [
                'testing_insert_table' => [
                    [
                        'id'     => 1,
                        'name'   => 'John P. Doe',
                        'height' => '9.000',
                        'unit'   => 'FEET',
                        'date'   => '2016-01-02 03:03:05'
                    ],
                    [
                        'id'     => 2,
                        'name'   => 'Kathrine Switzer',
                        'height' => '1.7500',
                        'unit'   => 'METER',
                        'date'   => '2016-01-12 14:45:50'
                    ],
                    [
                        'id'     => 3,
                        'name'   => 'Anna Fischer',
                        'height' => '1.4500',
                        'unit'   => 'METER',
                        'date'   => '2016-03-12 14:45:50'
                    ],
                    [
                        'id'     => 4,
                        'name'   => 'Sabiha Gökçen',
                        'height' => '1.4700',
                        'unit'   => 'METER',
                        'date'   => '2016-03-31 08:10:23'
                    ],
                    [
                        'id'     => 5,
                        'name'   => 'Simone Segouin',
                        'height' => '164.0000',
                        'unit'   => 'CENTIMETER',
                        'date'   => '2016-04-07 14:23:17'
                    ],
                ]
            ]
        );

        $actual = new \PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
        $actual->addTable('testing_insert_table');

        $this->assertDataSetsEqual($expected, $actual);
    }

    public function testReplacingExistent()
    {
        $queryBuilder = $this->getMockBuilder(FlatFileQueryBuilder::class)
            ->setConstructorArgs([$this->getDoctrineConnection()])
            ->setMethods(
                [
                    'getSQL',
                    'getPath',
                ]
            )
            ->getMock()
        ;

        $sql =<<<SQL_EOF
LOAD DATA INFILE {$this->getDoctrineConnection()->quote(__DIR__ . '/../data/flat_file_persister.csv')}
REPLACE INTO TABLE testing_insert_table
CHARACTER SET 'UTF8'
FIELDS
    TERMINATED BY ','
    OPTIONALLY ENCLOSED BY '"'
    ESCAPED BY '\\\\'
LINES
    TERMINATED BY '\\n'
(id, name, height, unit, date)
SQL_EOF;

        $queryBuilder->method('getSQL')->willReturn($sql);
        $queryBuilder->method('getPath')->willReturn(__DIR__ . '/../data/flat_file_persister.csv');

        $unitOfWork = $this->getMockForAbstractClass(
            UnitOfWorkInterface::class, [], '', false, false, true,
            [
                'enqueue',
                'getIterator'
            ]
        );

        $unitOfWork->method('enqueue');

        $unitOfWork->method('getIterator')->willReturn(new \ArrayIterator(
            [
                [
                    'id'     => 4,
                    'name'   => 'Sabiha Gökçen',
                    'height' => 1.47,
                    'unit'   => 'METER',
                    'date'   => '2016-03-31 08:10:23'
                ],
                [
                    'id'     => 5,
                    'name'   => 'Simone Segouin',
                    'height' => 164,
                    'unit'   => 'CENTIMETER',
                    'date'   => '2016-04-07 14:23:17'
                ],
            ]
        ));

        /** @var FlatFilePersisterInterface $persister */
        $persister = $this->getMockForAbstractClass(
            AbstractMySQLFlatFilePersister::class,
            [
                $this->getDoctrineConnection(),
                $queryBuilder,
                $unitOfWork,
                new MySQLConfigurationManager($this->getDoctrineConnection())
            ]
        );

        $initial = new \PHPUnit_Extensions_Database_DataSet_ArrayDataSet(
            [
                'testing_insert_table' => [
                    [
                        'id'     => 1,
                        'name'   => 'John P. Doe',
                        'height' => '9.000',
                        'unit'   => 'FEET',
                        'date'   => '2016-01-02 03:03:05'
                    ],
                    [
                        'id'     => 2,
                        'name'   => 'Kathrine Switzer',
                        'height' => '1.7500',
                        'unit'   => 'METER',
                        'date'   => '2016-01-12 14:45:50'
                    ],
                    [
                        'id'     => 3,
                        'name'   => 'Anna Fischer',
                        'height' => '1.4500',
                        'unit'   => 'METER',
                        'date'   => '2016-03-12 14:45:50'
                    ],
                    [
                        'id'     => 4,
                        'name'   => 'Jane W. Doe',
                        'height' => '1.4700',
                        'unit'   => 'METER',
                        'date'   => '2016-03-31 08:10:23'
                    ],
                    [
                        'id'     => 5,
                        'name'   => 'Marie Dupont',
                        'height' => '164.0000',
                        'unit'   => 'CENTIMETER',
                        'date'   => '2016-04-07 14:23:17'
                    ],
                ]
            ]
        );

        $this->getSetUpOperation()->execute($this->getConnection(), $initial);

        $expected = new \PHPUnit_Extensions_Database_DataSet_ArrayDataSet(
            [
                'testing_insert_table' => [
                    [
                        'id'     => 1,
                        'name'   => 'John P. Doe',
                        'height' => '9.000',
                        'unit'   => 'FEET',
                        'date'   => '2016-01-02 03:03:05'
                    ],
                    [
                        'id'     => 2,
                        'name'   => 'Kathrine Switzer',
                        'height' => '1.7500',
                        'unit'   => 'METER',
                        'date'   => '2016-01-12 14:45:50'
                    ],
                    [
                        'id'     => 3,
                        'name'   => 'Anna Fischer',
                        'height' => '1.4500',
                        'unit'   => 'METER',
                        'date'   => '2016-03-12 14:45:50'
                    ],
                    [
                        'id'     => 4,
                        'name'   => 'Sabiha Gökçen',
                        'height' => '1.4700',
                        'unit'   => 'METER',
                        'date'   => '2016-03-31 08:10:23'
                    ],
                    [
                        'id'     => 5,
                        'name'   => 'Simone Segouin',
                        'height' => '164.0000',
                        'unit'   => 'CENTIMETER',
                        'date'   => '2016-04-07 14:23:17'
                    ],
                ]
            ]
        );

        $actual = new \PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
        $actual->addTable('testing_insert_table');

        $this->assertDataSetsEqual($initial, $actual);

        $index = 3;
        foreach ($persister->flush() as $newItem) {
            $this->assertInternalType('array', $newItem);
            $this->assertArrayHasKey('id', $newItem);
            $this->assertEquals(++$index, $newItem['id']);
        }

        $actual = new \PHPUnit_Extensions_Database_DataSet_QueryDataSet($this->getConnection());
        $actual->addTable('testing_insert_table');

        $this->assertDataSetsEqual($expected, $actual);
    }
}
