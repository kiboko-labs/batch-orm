<?php

namespace Kiboko\Component\BatchORM\Persister;

use Kiboko\Component\BatchORM\Persister\QueryBuilder\FlatFileQueryBuilderInterface;

interface FlatFilePersisterInterface extends PersisterInterface
{
    /**
     * @param FlatFileQueryBuilderInterface $queryBuilder
     */
    public function setQueryBuilder(FlatFileQueryBuilderInterface $queryBuilder);
}
