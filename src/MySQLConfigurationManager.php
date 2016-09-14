<?php

namespace Kiboko\Component\BatchORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Platforms\MySqlPlatform;

class MySQLConfigurationManager implements RDBMSConfigurationManagerInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * MySQLConfigurationManager constructor.
     *
     * @param Connection $connection
     *
     * @throws DBALException
     */
    public function __construct(Connection $connection)
    {
        if (!$connection->getDatabasePlatform() instanceof MySqlPlatform) {
            throw DBALException::invalidPlatformSpecified();
        }

        $this->connection = $connection;
    }

    /**
     * @param string[] $desiredModes
     * @return string[]
     */
    public function addSessionModes(array $desiredModes)
    {
        $statement = $this->connection
            ->executeQuery('SELECT @@SESSION.sql_mode');
        $statement->execute();

        $originalModes = explode(',', $statement->fetchColumn());

        $difference = array_diff($desiredModes, $originalModes);
        if (count($difference)) {
            $this->connection->exec(sprintf('SET SESSION sql_mode="%s"',
                implode(',', array_merge($originalModes, $desiredModes))
            ));
        }

        return $originalModes;
    }

    /**
     * @param string[] $excludedModes
     * @return string[]
     */
    public function removeSessionModes(array $excludedModes)
    {
        $statement = $this->connection
            ->executeQuery('SELECT @@SESSION.sql_mode');
        $statement->execute();

        $originalModes = explode(',', $statement->fetchColumn());

        $difference = array_diff($originalModes, $excludedModes);
        if (count($difference)) {
            $this->connection->exec(sprintf('SET SESSION sql_mode="%s"',
                implode(',', $difference)
            ));
        }

        return $originalModes;
    }

    /**
     * @param string[] $originalModes
     */
    public function restoreSessionModes(array $originalModes)
    {
        $this->connection->exec(sprintf('SET SESSION sql_mode="%s"',
            implode(',', $originalModes)
        ));
    }
}
