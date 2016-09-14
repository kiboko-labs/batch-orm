<?php

namespace Kiboko\Component\BatchORM\Repository\QueryBuilder;

use Kiboko\Component\BatchORM\QueryFilter\QueryVisitorInterface;

interface QueryBuilderInterface
{
    /**
     * @return string
     */
    public function getSQL();

    /**
     * @return string
     */
    public function __toString();

    /**
     * @param QueryVisitorInterface $visitor
     */
    public function applyVisitor(QueryVisitorInterface $visitor);
}
