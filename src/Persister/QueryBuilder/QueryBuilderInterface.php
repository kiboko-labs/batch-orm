<?php

namespace Kiboko\Component\BatchORM\Persister\QueryBuilder;

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

    /**
     * @return void
     */
    public function setReplaceStrategy();

    /**
     * @return void
     */
    public function setIgnoreStrategy();

    /**
     * @return void
     */
    public function setInsertStrategy();
}
