<?php

namespace Kiboko\Component\BatchORM\Persister\QueryBuilder\MySQL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Query\QueryException;
use Kiboko\Component\BatchORM\Persister\QueryBuilder\FlatFileQueryBuilderInterface;
use Kiboko\Component\BatchORM\QueryFilter\QueryVisitorInterface;

/**
 * Trait FlatFileQueryBuilderTrait
 *
 * @package Kiboko\Component\BatchORM\Persister\QueryBuilder\MySQL
 * @see Kiboko\Component\BatchORM\Persister\QueryBuilder\FlatFileQueryBuilderInterface
 */
class FlatFileQueryBuilder implements FlatFileQueryBuilderInterface
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var bool
     */
    private $local;

    /**
     * @var string
     */
    private $strategy;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $table;

    /**
     * @var string[]
     */
    private $fields = [];

    /**
     * @var string
     */
    private $enclosing = '"';

    /**
     * @var string
     */
    private $delimiter = ',';

    /**
     * @var string
     */
    private $escaper = '\\';

    /**
     * @var string
     */
    private $lineTerminator = '\n';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * FlatFileQueryBuilderTrait constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Connection
     */
    protected function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return bool
     */
    public function isLocal()
    {
        return $this->local;
    }

    /**
     * @return bool
     */
    public function isRemote()
    {
        return !$this->local;
    }

    /**
     * @return void
     */
    public function setReplaceStrategy()
    {
        $this->strategy = 'REPLACE';
    }

    /**
     * @return void
     */
    public function setIgnoreStrategy()
    {
        $this->strategy = 'IGNORE';
    }

    /**
     * @return void
     */
    public function setInsertStrategy()
    {
        $this->strategy = null;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @param bool $isLocal
     * @return void
     */
    public function setPath($path, $isLocal = false)
    {
        $this->path = $path;
        $this->local = (bool) $isLocal;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param string $table
     * @return void
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * @return string
     */
    public function getEnclosing()
    {
        return $this->enclosing;
    }

    /**
     * @param string $enclosing
     * @return void
     */
    public function setEnclosing($enclosing)
    {
        $this->enclosing = $enclosing;
    }

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * @param string $delimiter
     * @return void
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @return string
     */
    public function getEscaper()
    {
        return $this->escaper;
    }

    /**
     * @param string $escaper
     * @return void
     */
    public function setEscaper($escaper)
    {
        $this->escaper = $escaper;
    }

    /**
     * @return string
     */
    public function getLineTerminator()
    {
        return $this->lineTerminator;
    }

    /**
     * @param string $lineTerminator
     * @return void
     */
    public function setLineTerminator($lineTerminator)
    {
        $this->lineTerminator = $lineTerminator;
    }

    /**
     * @return string[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param string[] $fields
     * @return void
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * @param string $field
     * @return void
     */
    public function addField($field)
    {
        $this->fields[] = $field;
    }

    /**
     * @param Connection $connection
     */
    protected function initializeQueryBuilder(Connection $connection)
    {
        $this->queryBuilder = new QueryBuilder($connection);
    }

    /**
     * @return QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @return string
     */
    private function serializeFields()
    {
        $fieldsCopy = $this->fields;
        array_walk($fieldsCopy, function($item, $key, Connection $connection){
            return $connection->quoteIdentifier($item);
        }, $this->getConnection());

        return implode(', ', $fieldsCopy);
    }

    /**
     * @return string
     */
    private function serializeLocal()
    {
        return $this->isLocal() ? 'LOCAL' : '';
    }

    /**
     * @return string
     */
    private function serializeStrategy()
    {
        return $this->strategy;
    }

    /**
     * @return string
     */
    private function serializeLineJump()
    {
        return $this->isFistLineIgnored() ? 'IGNORE 1 LINES' : '';
    }

    /**
     * @return string
     */
    public function getSQL()
    {
        return<<<SQL_EOF
LOAD DATA {$this->serializeLocal()}
INFILE {$this->getConnection()->quote($this->getPath())}
{$this->serializeStrategy()} INTO TABLE {$this->getConnection()->quoteIdentifier($this->getTable())}
CHARACTER SET 'UTF8'
FIELDS
    TERMINATED BY {$this->getConnection()->quote($this->getDelimiter())}
    OPTIONALLY ENCLOSED BY {$this->getConnection()->quote($this->getEnclosing())}
    ESCAPED BY {$this->getConnection()->quote($this->getEscaper())}
LINES
    TERMINATED BY {$this->getConnection()->quote($this->getLineTerminator())}
{$this->serializeLineJump()}
({$this->serializeFields()})
SQL_EOF;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            return (string) $this->getSQL();
        } catch (\Exception $e) {
            // Hide the exceptions in __toString() magic-method, for PHP 5.x BC
        } catch (\Throwable $e) {
            // Hide the exceptions in __toString() magic-method, here PHP 7+ engine exceptions
        }

        return null;
    }

    /**
     * @param QueryVisitorInterface $visitor
     */
    public function applyVisitor(QueryVisitorInterface $visitor)
    {
        $visitor->applyTo($this->queryBuilder);
    }
}
