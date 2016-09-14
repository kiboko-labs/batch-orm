<?php

namespace Kiboko\Component\BatchORM\QueryFilter;

use Doctrine\DBAL\Query\QueryBuilder;

interface QueryVisitorInterface
{
    /**
     * @param QueryBuilder $query
     */
    public function applyTo(QueryBuilder $query);
}
