<?php

namespace Kiboko\Component\BatchORM\Persister\QueryBuilder;

/**
 * Interface FlatFileQueryBuilderInterface
 *
 * @package Kiboko\Component\BatchORM\Persister\QueryBuilder\MySQL
 * @see Kiboko\Component\BatchORM\Persister\QueryBuilder\QueryBuilderInterface
 */
interface FlatFileQueryBuilderInterface extends QueryBuilderInterface
{
    /**
     * @return bool
     */
    public function isLocal();

    /**
     * @return bool
     */
    public function isRemote();

    /**
     * @param string $path
     * @param bool $isLocal
     * @return void
     */
    public function setPath($path, $isLocal = false);

    /**
     * @return string
     */
    public function getPath();

    /**
     * @param string $table
     * @return void
     */
    public function setTable($table);

    /**
     * @return string
     */
    public function getTable();

    /**
     * @param string $enclosing
     * @return void
     */
    public function setEnclosing($enclosing);

    /**
     * @return string
     */
    public function getEnclosing();

    /**
     * @param string $delimiter
     * @return void
     */
    public function setDelimiter($delimiter);

    /**
     * @return string
     */
    public function getDelimiter();

    /**
     * @param string $escaper
     * @return void
     */
    public function setEscaper($escaper);

    /**
     * @return string
     */
    public function getEscaper();

    /**
     * @param string[] $fields
     * @return void
     */
    public function setFields(array $fields);

    /**
     * @param string $field
     * @return void
     */
    public function addField($field);

    /**
     * @return string
     */
    public function getFields();
}
