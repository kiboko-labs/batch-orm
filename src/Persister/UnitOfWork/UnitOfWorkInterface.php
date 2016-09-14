<?php

namespace Kiboko\Component\BatchORM\Persister\UnitOfWork;

interface UnitOfWorkInterface extends \IteratorAggregate
{
    /**
     * @param object $value
     */
    public function enqueue($value);

    /**
     * @return bool
     */
    public function isFirstLineIgnored();
}
