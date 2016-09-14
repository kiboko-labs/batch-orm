<?php

namespace Kiboko\Component\BatchORM\Repository;

use Doctrine\DBAL\DBALException;
use Kiboko\Component\BatchORM\Repository\QueryBuilder\QueryBuilderInterface;

interface RepositoryInterface
{
    /**
     * @param QueryBuilderInterface $queryBuilder
     * @return void
     */
    public function apply(QueryBuilderInterface $queryBuilder);

    /**
     * @param string $expectedType
     * @param array $options
     * @return \Traversable
     * @throws DBALException
     */
    public function walkResults($expectedType, array $options = []);
}
