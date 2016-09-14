<?php

namespace unit\Kiboko\Component\BatchORM;

use Doctrine\DBAL\DBALException;
use PHPUnit_Framework_TestCase as TestCase;
use Kiboko\Component\BatchORM\MySQLConfigurationManager;

class MySQLConfigurationManagerTest extends TestCase
{
    use TestCaseTrait;

    /**
     * @return \PHPUnit_Extensions_Database_DataSet_ArrayDataSet
     */
    protected function getDataSet()
    {
        return new \PHPUnit_Extensions_Database_DataSet_ArrayDataSet([]);
    }

    public function setUp()
    {
        parent::setUp();
        $this->getPdoConnection()->exec('SET SESSION sql_mode=""');
    }

    public function tearDown()
    {
        $this->getPdoConnection()->exec('SET SESSION sql_mode=""');
        parent::tearDown();
    }

    protected function createDefaultDBConnection(PDO $connection, $schema = '')
    {
        return new \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection($connection, $schema);
    }

    protected function assertHasOption($option, $message = null)
    {
        $statement = $this->getPdoConnection()->prepare('SELECT @@SESSION.sql_mode');
        $statement->execute();

        $originalModes = explode(',', $statement->fetchColumn());

        $this->assertContains($option, $originalModes);
    }

    protected function assertHasNotOption($option, $message = null)
    {
        $statement = $this->getPdoConnection()->prepare('SELECT @@SESSION.sql_mode');
        $statement->execute();

        $originalModes = explode(',', $statement->fetchColumn());

        $this->assertNotContains($option, $originalModes);
    }

    public function testInvalidAddSessionMode()
    {
        $this->expectException(DBALException::class);
        
        $manager = new MySQLConfigurationManager(
            $this->getDoctrineConnection()
        );
        
        $manager->addSessionModes(['INVALID']);
    }

    public function testAddSessionModeWhenNotPresent()
    {
        $manager = new MySQLConfigurationManager(
            $this->getDoctrineConnection()
        );

        $manager->addSessionModes(['NO_AUTO_VALUE_ON_ZERO']);

        $this->assertHasOption('NO_AUTO_VALUE_ON_ZERO');
    }

    public function testAddSessionModeWhenIsPresent()
    {
        $this->getPdoConnection()->exec('SET SESSION sql_mode="NO_AUTO_VALUE_ON_ZERO"');

        $manager = new MySQLConfigurationManager(
            $this->getDoctrineConnection()
        );

        $manager->addSessionModes(['NO_AUTO_VALUE_ON_ZERO']);

        $this->assertHasOption('NO_AUTO_VALUE_ON_ZERO');
    }

    public function testRemoveSessionModeWhenNotPresent()
    {
        $manager = new MySQLConfigurationManager(
            $this->getDoctrineConnection()
        );

        $manager->removeSessionModes(['NO_AUTO_VALUE_ON_ZERO']);

        $this->assertHasNotOption('NO_AUTO_VALUE_ON_ZERO');
    }

    public function testRemoveSessionModeWhenIsPresent()
    {
        $this->getPdoConnection()->exec('SET SESSION sql_mode="NO_AUTO_VALUE_ON_ZERO"');

        $manager = new MySQLConfigurationManager(
            $this->getDoctrineConnection()
        );

        $manager->removeSessionModes(['NO_AUTO_VALUE_ON_ZERO']);

        $this->assertHasOption('NO_AUTO_VALUE_ON_ZERO');
    }
}
