<?php

namespace unit\Kiboko\Component\BatchORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

trait TestCaseTrait
{
    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var \PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    private $connection;

    /**
     * @var Connection
     */
    private $doctrineConnection;

    /**
     * @param \PDO $connection
     * @param string $schema
     * @return mixed
     */
    abstract protected function createDefaultDBConnection(\PDO $connection, $schema = '');

    /**
     * @return \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection|\PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    final public function getConnection()
    {
        if ($this->connection === null) {
            $this->connection = $this->createDefaultDBConnection($this->getPdoConnection(), $GLOBALS['DB_NAME']);
        }

        return $this->connection;
    }

    /**
     * @return \PDO
     */
    private function getPdoConnection()
    {
        if ($this->pdo === null) {
            $dsn = strtr('mysql:dbname=%dbname%;hostname=%host%;port=%port%',
                [
                    '%dbname%' => $GLOBALS['DB_NAME'],
                    '%host%'   => isset($GLOBALS['DB_HOSTNAME']) ? $GLOBALS['DB_HOSTNAME'] : '127.0.0.1',
                    '%port%'   => isset($GLOBALS['DB_PORT'])     ? $GLOBALS['DB_PORT']     : 3306,
                ]
            );

            $this->pdo = new \PDO($dsn, $GLOBALS['DB_USERNAME'], $GLOBALS['DB_PASSWORD']);
        }

        return $this->pdo;
    }

    /**
     * @return \Doctrine\DBAL\Connection
     */
    protected function getDoctrineConnection()
    {
        if ($this->doctrineConnection === null) {
            $this->doctrineConnection = DriverManager::getConnection([
                'pdo' => $this->getPdoConnection(),
            ]);
        }

        return $this->doctrineConnection;
    }
}
