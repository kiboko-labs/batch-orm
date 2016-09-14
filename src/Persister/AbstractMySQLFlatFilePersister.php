<?php

namespace Kiboko\Component\BatchORM\Persister;

use Doctrine\DBAL\Connection;
use Kiboko\Component\BatchORM\Persister\FlatFilePersisterInterface;
use Kiboko\Component\BatchORM\MySQLConfigurationManager;
use Kiboko\Component\BatchORM\Persister\QueryBuilder\FlatFileQueryBuilderInterface;
use Kiboko\Component\BatchORM\Persister\UnitOfWork\UnitOfWorkInterface;

abstract class AbstractMySQLFlatFilePersister implements FlatFilePersisterInterface
{
    const MYSQL_OPTION_NO_AUTO_VALUE_ON_ZERO = 'NO_AUTO_VALUE_ON_ZERO';

    /**
     * @var FlatFileQueryBuilderInterface
     */
    private $queryBuilder;

    /**
     * @var UnitOfWorkInterface
     */
    private $unitOfWork;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var MySQLConfigurationManager
     */
    private $configurationManager;

    /**
     * MySQLFlatFilePersister constructor.
     * @param Connection $connection
     * @param FlatFileQueryBuilderInterface $queryBuilder
     * @param UnitOfWorkInterface $unitOfWork
     * @param MySQLConfigurationManager $configurationManager
     */
    public function __construct(
        Connection $connection,
        FlatFileQueryBuilderInterface $queryBuilder,
        UnitOfWorkInterface $unitOfWork,
        MySQLConfigurationManager $configurationManager
    ) {
        $this->connection = $connection;
        $this->setQueryBuilder($queryBuilder);
        $this->unitOfWork = $unitOfWork;
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param FlatFileQueryBuilderInterface $queryBuilder
     */
    public function setQueryBuilder(FlatFileQueryBuilderInterface $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @param object $object
     */
    public function persist($object)
    {
        $this->unitOfWork->enqueue($object);
    }

    /**
     * @return \Traversable
     */
    public function flush()
    {
        $originalModes = $this->configurationManager->addSessionModes([
            self::MYSQL_OPTION_NO_AUTO_VALUE_ON_ZERO,
        ]);

        if ($this->connection->exec($this->queryBuilder->getSQL()) === false) {
            $this->configurationManager->restoreSessionModes($originalModes);

            throw new \RuntimeException(sprintf('Failed to import data from file %s', $this->queryBuilder->getPath()));
        }

        $this->configurationManager->restoreSessionModes($originalModes);

        $identifier = $this->connection->lastInsertId();
        foreach ($this->unitOfWork as $object) {
            if (!$this->hasIdentifier($object)) {
                $this->persistedToId($object, $identifier++);
                yield $object;
            }
        }
    }

    /**
     * @param object $object
     * @return bool
     */
    abstract protected function hasIdentifier($object);

    /**
     * @param object $object
     * @param int $identifier
     */
    abstract protected function persistedToId($object, $identifier);
}
