<?php

namespace Kiboko\Component\BatchORM\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Kiboko\Component\BatchORM\Repository\QueryBuilder\FlatFileQueryBuilderInterface;
use Kiboko\Component\BatchORM\Repository\QueryBuilder\QueryBuilderInterface;
use Symfony\Component\Serializer\SerializerInterface;

class MySQLFlatFileRepository implements FlatFileRepositoryInterface
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var FlatFileQueryBuilderInterface
     */
    private $queryBuilder;

    /**
     * MySQLFlatFileRepository constructor.
     *
     * @param Connection $connection
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Connection $connection,
        SerializerInterface $serializer
    ) {
        $this->connection = $connection;
        $this->serializer = $serializer;
    }
    /**
     * @param QueryBuilderInterface $queryBuilder
     * @return void
     * @throws DBALException
     */
    public function apply(QueryBuilderInterface $queryBuilder)
    {
        if (!$queryBuilder instanceof FlatFileQueryBuilderInterface) {
            throw new DBALException('Wrong query builder, was expecting ' . FlatFileQueryBuilderInterface::class
                . ', got ' . QueryBuilderInterface::class . '.');
        }

        $this->queryBuilder = $queryBuilder;

        if ($this->connection->exec($queryBuilder->getSQL()) === false) {
            throw new DBALException('Could not execute query.');
        }
    }

    /**
     * @return void
     */
    public function cleanup()
    {
        if (file_exists($this->queryBuilder->getPath())) {
            unlink($this->queryBuilder->getPath());
        }
    }

    /**
     * @param string $expectedType
     * @param array $options
     * @return \Traversable
     */
    public function walkResults($expectedType, array $options = [])
    {
        $file = new \SplFileObject($this->queryBuilder->getPath());

        $file->openFile('r');
        $file->setCsvControl(
            $this->queryBuilder->getDelimiter(),
            $this->queryBuilder->getEnclosing(),
            $this->queryBuilder->getEscaper()
        );
        $file->setFlags(\SplFileObject::READ_CSV);

        $iterator = new \NoRewindIterator($file);
        if (!$this->queryBuilder->hasHeaderLine()) {
            $headers = $this->queryBuilder->getQueryBuilder()->getQueryPart('select');
        } else {
            $headers = $iterator->current();
            $iterator->next();
        }
        $headersCount = count($headers);

        foreach ($iterator as $line) {
            if (count($line) !== $headersCount) {
                continue;
            }

            yield $this->serializer->deserialize(array_combine($headers, $line), $expectedType, 'array');
        }
    }
}
