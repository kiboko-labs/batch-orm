<?php

namespace Kiboko\Component\BatchORM\Repository\QueryBuilder;

use Doctrine\DBAL\Query\QueryBuilder;

interface FlatFileQueryBuilderInterface extends QueryBuilderInterface
{
    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder();

    /**
     * @return bool
     */
    public function hasHeaderLine();

    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return $this
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder);

    /**
     * @return string
     */
    public function getPath();

    /**
     * @param string $path
     * @return void
     */
    public function setPath($path);

    /**
     * @return string
     */
    public function getEnclosing();

    /**
     * @param string $enclosing
     * @return void
     */
    public function setEnclosing($enclosing);

    /**
     * @return string
     */
    public function getDelimiter();

    /**
     * @param string $delimiter
     * @return void
     */
    public function setDelimiter($delimiter);

    /**
     * @return string
     */
    public function getEscaper();

    /**
     * @param string $escaper
     * @return void
     */
    public function setEscaper($escaper);

    /**
     * @return string
     */
    public function getLineTerminator();

    /**
     * @param string $lineTerminator
     * @return void
     */
    public function setLineTerminator($lineTerminator);
}
