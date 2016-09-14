<?php

namespace Kiboko\Component\BatchORM\Repository\QueryBuilder\MySQL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Query\QueryException;
use Kiboko\Component\BatchORM\Repository\QueryBuilder\FlatFileQueryBuilderInterface;
use Kiboko\Component\BatchORM\QueryFilter\QueryVisitorInterface;

/**
 * Trait FlatFileQueryBuilder
 *
 * @package Kiboko\Component\BatchORM\Repository\QueryBuilder\MySQL
 * @see Kiboko\Component\BatchORM\Repository\QueryBuilder\FlatFileQueryBuilderInterface
 */
class FlatFileQueryBuilder implements FlatFileQueryBuilderInterface
{
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var string
     */
    private $path;

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
     * @var bool
     */
    private $hasHeaderLine;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * FlatFileQueryBuilderTrait constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection, $hasHeaderLine = true)
    {
        $this->connection = $connection;
        $this->queryBuilder = $connection->createQueryBuilder();
        $this->hasHeaderLine = $hasHeaderLine;
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
    public function hasHeaderLine()
    {
        return $this->hasHeaderLine;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return $this
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
        return $this;
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
     * @return void
     */
    public function setPath($path)
    {
        $this->path = $path;
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
     * @param array $joinParts
     * @param array $fromParts
     *
     * @return string[]
     */
    private function getFromClauses(array $joinParts, array $fromParts)
    {
        $fromClauses = array();
        $knownAliases = array();

        // Loop through all FROM clauses
        foreach ($fromParts as $from) {
            if ($from['alias'] === null) {
                $tableSql = $from['table'];
                $tableReference = $from['table'];
            } else {
                $tableSql = $from['table'] . ' ' . $from['alias'];
                $tableReference = $from['alias'];
            }

            $knownAliases[$tableReference] = true;

            $fromClauses[$tableReference] = $tableSql . $this->getSQLForJoins($tableReference, $knownAliases);
        }

        $this->verifyAllAliasesAreKnown($joinParts, $knownAliases);

        return $fromClauses;
    }

    /**
     * @param array $joinParts
     * @param array $knownAliases
     *
     * @throws QueryException
     */
    private function verifyAllAliasesAreKnown(array $joinParts, array $knownAliases)
    {
        foreach ($joinParts as $fromAlias => $joins) {
            if ( ! isset($knownAliases[$fromAlias])) {
                throw QueryException::unknownAlias($fromAlias, array_keys($knownAliases));
            }
        }
    }

    /**
     * @return string
     */
    private function serializeFields()
    {
        return implode(', ', $this->queryBuilder->getQueryPart('select'));
    }

    /**
     * @return string
     */
    private function serializeFrom()
    {
        $fromParts = $this->queryBuilder->getQueryPart('from');
        $joinParts = $this->queryBuilder->getQueryPart('join');

        return ($fromParts ? ' FROM ' . implode(', ', $this->getFromClauses($joinParts, $fromParts)) : '');
    }

    /**
     * @return string
     */
    private function serializeWhere()
    {
        $whereParts = $this->queryBuilder->getQueryPart('where');

        return ($whereParts !== null ? ' WHERE ' . ((string) $whereParts) : '');
    }

    /**
     * @return string
     */
    private function serializeGroupBy()
    {
        $groupByParts = $this->queryBuilder->getQueryPart('groupBy');

        return ($groupByParts ? ' GROUP BY ' . implode(', ', $groupByParts) : '');
    }

    /**
     * @return string
     */
    private function serializeHaving()
    {
        $havingParts = $this->queryBuilder->getQueryPart('having');

        return ($havingParts !== null ? ' HAVING ' . ((string) $havingParts) : '');
    }

    /**
     * @return string
     */
    private function serializeOrderBy()
    {
        $orderByParts = $this->queryBuilder->getQueryPart('orderBy');

        return ($orderByParts ? ' ORDER BY ' . implode(', ', $orderByParts) : '');
    }

    /**
     * @return string
     */
    private function serializeHeaders()
    {
        $fieldsCopy = $this->queryBuilder->getQueryPart('select');
        array_walk($fieldsCopy, function($item, $key, Connection $connection){
            return $connection->quoteIdentifier($item);
        }, $this->getConnection());

        return 'SELECT ' . implode(', ', $fieldsCopy) . ' UNION ALL ';
    }

    /**
     * @return string
     */
    public function getSQL()
    {
        return <<<SQL_EOF
{$this->serializeHeaders()}
SELECT {$this->serializeFields()}
INTO OUTFILE {$this->getConnection()->quote($this->getPath())}
FIELDS 
    TERMINATED BY {$this->getConnection()->quote($this->getDelimiter())}
    OPTIONALLY ENCLOSED BY {$this->getConnection()->quote($this->getEnclosing())}
    ESCAPED BY {$this->getConnection()->quote($this->getEscaper())}
LINES 
    TERMINATED BY {$this->getConnection()->quote($this->getLineTerminator())}
{$this->serializeFrom()}
{$this->serializeWhere()}
{$this->serializeGroupBy()}
{$this->serializeHaving()}
{$this->serializeOrderBy()}
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
